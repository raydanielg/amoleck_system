<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\AnnouncementReceipt;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\DocumentLink;
use App\Models\DocumentPermission;
use App\Models\DocumentShare;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    public const ALL_CAPS = ['view', 'preview', 'download', 'upload', 'edit', 'share', 'delete', 'versions', 'perms', 'approve'];

    /** Max file size (KB) + allowed mimes come from settings. */
    public static function uploadRules(): array
    {
        $maxKb = (int) (\App\Support\Settings::get('doc_max_size_kb', 10240));
        $mimes = \App\Support\Settings::get('doc_allowed_mimes', 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png,webp,zip');
        return ['file' => ['required', 'file', 'max:'.$maxKb, 'mimes:'.$mimes]];
    }

    /** Resolved capability set for a user on a document. */
    public static function capsOnDocument(User $u, Document $doc): array
    {
        if ($u->isAdmin()) return self::ALL_CAPS;
        if (! $u->hasPermission('docs.view')) return [];
        if ($doc->owner_id === $u->id) return array_diff(self::ALL_CAPS, ['approve', 'perms']) + ($u->hasPermission('docs.perms') ? ['perms'] : []);

        $caps = [];
        // 1. Folder visibility + inherited folder grants (deepest wins, deny subtracts).
        if ($doc->folder) {
            $caps = self::folderCaps($u, $doc->folder);
        }
        // 2. Document-level grants override/add.
        $caps = self::applyGrants($u, $caps, DocumentPermission::where('permissible_type', Document::class)->where('permissible_id', $doc->id)->get());
        // 3. Active shares add caps.
        $caps = self::applyGrants($u, $caps, DocumentShare::active()->where('shareable_type', Document::class)->where('shareable_id', $doc->id)->get(), true);

        // Module-level gates: capability is useless without the base permission.
        return array_values(array_filter($caps, fn ($c) => $u->hasPermission('docs.'.$c) || in_array($c, ['view', 'preview', 'approve', 'perms'])));
    }

    /** Resolved capabilities on a folder (incl. ancestor grants + visibility). */
    public static function folderCaps(User $u, DocumentFolder $folder): array
    {
        if ($u->isAdmin()) return self::ALL_CAPS;
        if (! $u->hasPermission('docs.view')) return [];

        $caps = [];
        if ($folder->visibility === 'org') $caps = ['view', 'preview'];
        if ($folder->visibility === 'department' && $folder->department_id && $folder->department_id === $u->employee?->department_id) $caps = ['view', 'preview'];
        if ($folder->visibility === 'branch' && $folder->branch_id && $folder->branch_id === $u->employee?->branch_id) $caps = ['view', 'preview'];
        if ($folder->owner_id === $u->id) $caps = array_diff(self::ALL_CAPS, ['approve', 'perms']);

        // Walk root → leaf so deeper grants override.
        $chain = $folder->ancestors();
        foreach ($chain as $node) {
            $grants = DocumentPermission::where('permissible_type', DocumentFolder::class)->where('permissible_id', $node->id)->get();
            $caps = self::applyGrants($u, $caps, $grants);
            $shares = DocumentShare::active()->where('shareable_type', DocumentFolder::class)->where('shareable_id', $node->id)->get();
            $caps = self::applyGrants($u, $caps, $shares, true);
        }
        return array_values(array_unique($caps));
    }

    private static function applyGrants(User $u, array $caps, iterable $grants, bool $fromShare = false): array
    {
        [$dept, $branch] = [$u->employee?->department_id, $u->employee?->branch_id];
        foreach ($grants as $g) {
            $match = ($g->grantee_type === 'user' && (int) $g->grantee_id === $u->id)
                || ($g->grantee_type === 'department' && $dept && (int) $g->grantee_id === $dept)
                || ($g->grantee_type === 'branch' && $branch && (int) $g->grantee_id === $branch);
            if (! $match) continue;
            $gcaps = $g->caps ?? [];
            if (! empty($g->deny)) $caps = array_diff($caps, $gcaps);
            else $caps = array_unique(array_merge($caps, $gcaps));
        }
        return array_values($caps);
    }

    public static function can(User $u, Document $doc, string $cap): bool
    {
        if ($cap === 'view') return in_array('view', self::capsOnDocument($u, $doc));
        return in_array($cap, self::capsOnDocument($u, $doc));
    }

    public static function canFolder(User $u, DocumentFolder $f, string $cap): bool
    {
        return in_array($cap, self::folderCaps($u, $f));
    }

    /** Folders visible to this user in browse lists. */
    public static function visibleFolders(User $u): Collection
    {
        if ($u->isAdmin()) return DocumentFolder::where('status', 'active')->get();
        return DocumentFolder::where('status', 'active')->get()->filter(fn ($f) => in_array('view', self::folderCaps($u, $f)))->values();
    }

    public static function storeFile(User $u, UploadedFile $file, ?DocumentFolder $folder, array $meta = []): Document
    {
        $doc = Document::create([
            'folder_id' => $folder?->id,
            'name' => $meta['name'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'description' => $meta['description'] ?? null,
            'reference' => $meta['reference'] ?? 'DOC-'.strtoupper(Str::random(8)),
            'category' => $meta['category'] ?? null,
            'department_id' => $folder?->department_id ?? $u->employee?->department_id,
            'branch_id' => $folder?->branch_id ?? $u->employee?->branch_id,
            'owner_id' => $u->id,
            'status' => 'active',
            'classification' => $meta['classification'] ?? 'internal',
            'created_by' => $u->id,
        ]);
        self::storeVersion($u, $doc, $file, $meta['note'] ?? 'Initial upload');
        AuditLogger::log('document.upload', 'Document', $doc->id, null, ['name' => $doc->name]);
        return $doc;
    }

    public static function storeVersion(User $u, Document $doc, UploadedFile $file, ?string $note = null): Document
    {
        // Validate real MIME server-side, not just extension.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file->getRealPath()) ?: $file->getMimeType();

        $v = ($doc->current_version ?? 0) + 1;
        $path = $file->storeAs('documents/'.$doc->id, "v{$v}_".$file->hashName().'.'.$file->getClientOriginalExtension(), 'local');
        $doc->versions()->create([
            'version' => $v,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $mime,
            'size' => $file->getSize(),
            'uploaded_by' => $u->id,
            'note' => $note,
        ]);
        $doc->update(['current_version' => $v]);
        AuditLogger::log('document.version', 'Document', $doc->id, null, ['version' => $v]);
        return $doc->fresh();
    }

    public static function streamVersion(\App\Models\DocumentVersion $version, bool $inline = false)
    {
        $response = Storage::disk('local')->response($version->path, $version->original_name);
        if ($inline) {
            $response->headers->set('Content-Disposition', 'inline; filename="'.addslashes($version->original_name).'"');
            if ($version->mime) $response->headers->set('Content-Type', $version->mime);
        }
        return $response;
    }

    /** Share with capability ceiling: granter cannot give what they don't have. */
    public static function share(User $u, Document|DocumentFolder $target, string $granteeType, int $granteeId, array $caps, ?string $expires = null, ?string $reason = null): DocumentShare
    {
        $mine = $target instanceof Document ? self::capsOnDocument($u, $target) : self::folderCaps($u, $target);
        if (! $u->isAdmin()) {
            abort_unless(in_array('share', $mine), 403);
            $caps = array_intersect($caps, array_merge($mine, ['view', 'preview'])); // can share viewing even if not explicit
        }
        $caps = array_values(array_intersect($caps, self::ALL_CAPS));
        abort_if(empty($caps), 422, 'No shareable capabilities');

        $share = DocumentShare::create([
            'shareable_type' => $target::class,
            'shareable_id' => $target->id,
            'shared_by' => $u->id,
            'grantee_type' => $granteeType,
            'grantee_id' => $granteeId,
            'caps' => $caps,
            'reason' => $reason,
            'expires_at' => $expires,
        ]);
        if ($granteeType === 'user') {
            $user = User::find($granteeId);
            if ($user) NotificationService::notify($user, 'document.shared', 'Document shared with you', $target->name.' shared by '.$u->name, route('documents.index', ['view' => 'shared']));
        }
        AuditLogger::log('document.share', class_basename($target), $target->id, null, ['grantee' => "$granteeType:$granteeId", 'caps' => $caps]);
        return $share;
    }

    public static function createLink(User $u, Document $doc, array $opts = []): DocumentLink
    {
        abort_unless($u->isAdmin() || $u->hasPermission('docs.external'), 403);
        abort_unless(self::can($u, $doc, 'share') || $u->isAdmin(), 403);
        abort_if($doc->classification === 'restricted', 422, 'Restricted documents cannot be shared externally');
        return DocumentLink::create([
            'document_id' => $doc->id,
            'token' => Str::random(48),
            'created_by' => $u->id,
            'mode' => $opts['mode'] ?? 'download',
            'password' => $opts['password'] ? bcrypt($opts['password']) : null,
            'expires_at' => $opts['expires_at'] ?? now()->addDays(7),
            'max_access' => $opts['max_access'] ?? null,
        ]);
    }

    /* ---------------- Document Board ---------------- */

    public static function announcementAudience(Announcement $a): \Illuminate\Database\Eloquent\Builder
    {
        $t = $a->targets ?? [];
        $q = User::query()->where('status', User::STATUS_ACTIVE);
        if (! empty($t['all'])) return $q;
        return $q->where(function ($w) use ($t) {
            if (! empty($t['roles'])) $w->orWhereIn('role', $t['roles']);
            if (! empty($t['users'])) $w->orWhereIn('id', $t['users']);
            if (! empty($t['departments'])) {
                $w->orWhereIn('id', \App\Models\Employee::whereIn('department_id', $t['departments'])->select('user_id'));
            }
            if (! empty($t['branches'])) {
                $w->orWhereIn('id', \App\Models\Employee::whereIn('branch_id', $t['branches'])->select('user_id'));
            }
            if (empty($t['roles']) && empty($t['users']) && empty($t['departments']) && empty($t['branches'])) $w->whereRaw('1=0');
        });
    }

    public static function publishAnnouncement(User $u, Announcement $a): Announcement
    {
        $a->update(['status' => 'published', 'published_by' => $u->id, 'published_at' => now()]);
        $recipients = self::announcementAudience($a)->pluck('id');
        foreach ($recipients as $uid) {
            AnnouncementReceipt::firstOrCreate(['announcement_id' => $a->id, 'user_id' => $uid], ['delivered_at' => now(), 'status' => 'delivered']);
        }
        foreach (User::whereIn('id', $recipients)->get() as $usr) {
            NotificationService::notify($usr, 'announcement', $a->title, $a->summary, route('documents.board'));
        }
        AuditLogger::log('announcement.publish', 'Announcement', $a->id, null, ['title' => $a->title, 'recipients' => $recipients->count()]);
        return $a;
    }

    public static function announcementsFor(User $u)
    {
        return Announcement::live()->orderByDesc('pinned')->orderByDesc('published_at')
            ->whereHas('receipts', fn ($q) => $q->where('user_id', $u->id));
    }
}
