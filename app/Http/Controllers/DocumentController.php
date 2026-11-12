<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementReceipt;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\DocumentLink;
use App\Models\DocumentPermission;
use App\Models\DocumentShare;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DocumentService;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /* ================= File Manager ================= */

    public function index(Request $r)
    {
        $u = $r->user();
        abort_unless($u->isAdmin() || $u->hasPermission('docs.view'), 403);
        $view = $r->get('view', 'my');
        $folder = null;
        if ($r->filled('folder')) {
            $folder = DocumentFolder::findOrFail($r->folder);
            abort_unless(DocumentService::canFolder($u, $folder, 'view'), 403);
            $view = 'folder';
        }

        $docs = collect();
        $breadcrumbs = collect();
        if ($folder) {
            abort_if($folder->status !== 'active', 404);
            $docs = $folder->documents()->latest()->get()->filter(fn ($d) => DocumentService::can($u, $d, 'view'));
            $breadcrumbs = $folder->ancestors();
        } elseif ($view === 'my') {
            $docs = Document::where('owner_id', $u->id)->latest()->paginate(40);
        } elseif ($view === 'shared') {
            $ids = DocumentShare::active()->where(function ($q) use ($u) {
                $q->where(fn ($w) => $w->where('grantee_type', 'user')->where('grantee_id', $u->id));
                if ($u->employee?->department_id) $q->orWhere(fn ($w) => $w->where('grantee_type', 'department')->where('grantee_id', $u->employee->department_id));
                if ($u->employee?->branch_id) $q->orWhere(fn ($w) => $w->where('grantee_type', 'branch')->where('grantee_id', $u->employee->branch_id));
            })->where('shareable_type', Document::class)->pluck('shareable_id');
            $docs = Document::whereIn('id', $ids)->latest()->paginate(40);
        } elseif ($view === 'shared_by') {
            $ids = DocumentShare::where('shared_by', $u->id)->where('shareable_type', Document::class)->pluck('shareable_id');
            $docs = Document::whereIn('id', $ids)->latest()->paginate(40);
        } elseif ($view === 'starred') {
            $docs = Document::whereHas('starredBy', fn ($q) => $q->where('user_id', $u->id))->latest()->paginate(40);
        } elseif ($view === 'recent') {
            $docs = Document::latest('updated_at')->limit(60)->get()->filter(fn ($d) => DocumentService::can($u, $d, 'view'));
        } elseif ($view === 'trash') {
            abort_unless($u->isAdmin() || $u->hasPermission('docs.restore') || $u->hasPermission('docs.delete'), 403);
            $docs = Document::onlyTrashed()->where(fn ($q) => $q->where('owner_id', $u->id)->orWhere('created_by', $u->id))->latest()->paginate(40);
            if ($u->isAdmin()) $docs = Document::onlyTrashed()->latest()->paginate(40);
        }

        // Search (permission-filtered)
        if ($r->filled('q')) {
            $docs = Document::where('name', 'like', '%'.$r->q.'%')
                ->orWhere('reference', 'like', '%'.$r->q.'%')
                ->orWhere('description', 'like', '%'.$r->q.'%')
                ->latest()->limit(60)->get()
                ->filter(fn ($d) => DocumentService::can($u, $d, 'view'));
            $view = 'search';
        }

        return view('documents.index', [
            'view' => $view,
            'docs' => $docs,
            'folder' => $folder,
            'breadcrumbs' => $breadcrumbs,
            'subfolders' => $folder ? $folder->children()->where('status', 'active')->get()->filter(fn ($f) => DocumentService::canFolder($u, $f, 'view')) : collect(),
            'departments' => Department::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'folders' => DocumentService::visibleFolders($u)->whereNull('parent_id'),
            'users' => User::where('status', User::STATUS_ACTIVE)->orderBy('name')->get(),
            'q' => $r->q,
        ]);
    }

    public function storeFolder(Request $r)
    {
        $u = $r->user();
        abort_unless($u->isAdmin() || $u->hasPermission('docs.folders'), 403);
        $data = $r->validate([
            'name' => 'required|string|max:160', 'parent_id' => 'nullable|exists:document_folders,id',
            'department_id' => 'nullable|exists:departments,id', 'branch_id' => 'nullable|exists:branches,id',
            'visibility' => 'required|in:private,department,branch,org', 'description' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:80',
        ]);
        if (! empty($data['parent_id'])) {
            $parent = DocumentFolder::findOrFail($data['parent_id']);
            abort_unless(DocumentService::canFolder($u, $parent, 'upload') || DocumentService::canFolder($u, $parent, 'edit'), 403);
        }
        $f = DocumentFolder::create($data + ['owner_id' => $u->id, 'created_by' => $u->id]);
        AuditLogger::log('folder.create', 'DocumentFolder', $f->id, null, $data);
        return back()->with('success', 'Folder created');
    }

    public function upload(Request $r)
    {
        $u = $r->user();
        abort_unless($u->isAdmin() || $u->hasPermission('docs.upload'), 403);
        $r->validate(['folder_id' => 'nullable|exists:document_folders,id', 'description' => 'nullable|string|max:500', 'classification' => 'nullable|in:internal,confidential,restricted', 'name' => 'nullable|string|max:160', 'files.*' => DocumentService::uploadRules()['file']]);
        $folder = $r->folder_id ? DocumentFolder::findOrFail($r->folder_id) : null;
        if ($folder) abort_unless(DocumentService::canFolder($u, $folder, 'upload'), 403);
        foreach ($r->file('files', []) as $file) {
            DocumentService::storeFile($u, $file, $folder, $r->only('description', 'classification', 'name'));
        }
        return back()->with('success', 'File(s) uploaded');
    }

    private function doc(Request $r, $id): Document
    {
        return Document::withTrashed()->findOrFail($id);
    }

    public function show(Request $r, Document $document)
    {
        $u = $r->user();
        abort_unless(DocumentService::can($u, $document, 'view'), 403);
        return response()->json([
            'id' => $document->id, 'name' => $document->name, 'description' => $document->description,
            'reference' => $document->reference, 'owner' => $document->owner->name,
            'folder' => $document->folder?->name, 'classification' => $document->classification,
            'size' => $document->current?->size, 'mime' => $document->current?->mime,
            'version' => $document->current_version, 'updated' => $document->updated_at->toDayDateTimeString(),
            'caps' => DocumentService::capsOnDocument($u, $document),
            'starred' => $document->starredBy()->where('user_id', $u->id)->exists(),
            'versions' => $document->versions->map(fn ($v) => ['version' => $v->version, 'size' => $v->size, 'by' => $v->uploader->name, 'at' => $v->created_at->toDayDateTimeString(), 'note' => $v->note]),
            'is_previewable' => in_array($document->current?->mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'text/plain']),
        ]);
    }

    public function preview(Request $r, Document $document)
    {
        abort_unless(DocumentService::can($r->user(), $document, 'preview') || DocumentService::can($r->user(), $document, 'download'), 403);
        AuditLogger::log('document.preview', 'Document', $document->id);
        return DocumentService::streamVersion($document->current, true);
    }

    public function download(Request $r, Document $document, ?DocumentVersion $version = null)
    {
        abort_unless(DocumentService::can($r->user(), $document, 'download'), 403);
        $v = $version ?: $document->current;
        abort_unless($v && $v->document_id === $document->id, 404);
        if ($version) abort_unless(DocumentService::can($r->user(), $document, 'versions') || $version->version === $document->current_version, 403);
        AuditLogger::log('document.download', 'Document', $document->id, null, ['version' => $v->version]);
        return DocumentService::streamVersion($v);
    }

    public function update(Request $r, Document $document)
    {
        $u = $r->user();
        abort_unless(DocumentService::can($u, $document, 'edit'), 403);
        $data = $r->validate(['name' => 'sometimes|string|max:160', 'description' => 'nullable|string|max:500', 'category' => 'nullable|string|max:80', 'classification' => 'nullable|in:internal,confidential,restricted', 'folder_id' => 'nullable|exists:document_folders,id']);
        if (array_key_exists('folder_id', $data)) {
            abort_unless(DocumentService::can($u, $document, 'edit'), 403);
            if ($data['folder_id']) abort_unless(DocumentService::canFolder($u, DocumentFolder::findOrFail($data['folder_id']), 'upload'), 403);
        }
        AuditLogger::log('document.update', 'Document', $document->id, $document->only('name', 'description'), $data);
        $document->update($data);
        return back()->with('success', 'Document updated');
    }

    public function uploadVersion(Request $r, Document $document)
    {
        $u = $r->user();
        abort_unless(DocumentService::can($u, $document, 'upload') && $u->hasPermission('docs.versions'), 403);
        $r->validate(['file' => DocumentService::uploadRules()['file'], 'note' => 'nullable|string|max:255']);
        DocumentService::storeVersion($u, $document, $r->file('file'), $r->note);
        return back()->with('success', 'New version uploaded');
    }

    public function destroy(Request $r, Document $document)
    {
        $u = $r->user();
        abort_unless(DocumentService::can($u, $document, 'delete') || $document->owner_id === $u->id, 403);
        $document->delete();
        AuditLogger::log('document.trash', 'Document', $document->id);
        return back()->with('success', 'Moved to trash');
    }

    public function restore(Request $r, $id)
    {
        $doc = Document::onlyTrashed()->findOrFail($id);
        abort_unless($r->user()->isAdmin() || $doc->owner_id === $r->user()->id || $r->user()->hasPermission('docs.restore'), 403);
        $doc->restore();
        AuditLogger::log('document.restore', 'Document', $doc->id);
        return back()->with('success', 'Restored');
    }

    public function forceDelete(Request $r, $id)
    {
        $doc = Document::onlyTrashed()->findOrFail($id);
        abort_unless($r->user()->isAdmin() || $r->user()->hasPermission('docs.restore'), 403);
        AuditLogger::log('document.force_delete', 'Document', $doc->id, ['name' => $doc->name]);
        foreach ($doc->versions as $v) Storage::disk('local')->delete($v->path);
        $doc->forceDelete();
        return back()->with('success', 'Permanently deleted');
    }

    public function star(Request $r, Document $document)
    {
        abort_unless(DocumentService::can($r->user(), $document, 'view'), 403);
        $existing = $document->starredBy()->where('user_id', $r->user()->id);
        $existing->exists() ? $existing->delete() : $document->starredBy()->create(['user_id' => $r->user()->id]);
        return back();
    }

    public function share(Request $r, Document $document)
    {
        $u = $r->user();
        $data = $r->validate([
            'grantee_type' => 'required|in:user,department,branch',
            'grantee_id' => 'required|integer',
            'caps' => 'required|array|min:1', 'caps.*' => 'in:view,preview,download,upload,edit,share,delete,versions',
            'expires_at' => 'nullable|date|after:now', 'reason' => 'nullable|string|max:255',
        ]);
        DocumentService::share($u, $document, $data['grantee_type'], $data['grantee_id'], $data['caps'], $data['expires_at'] ?? null, $data['reason'] ?? null);
        return back()->with('success', 'Shared');
    }

    public function revokeShare(Request $r, DocumentShare $share)
    {
        abort_unless($r->user()->isAdmin() || $share->shared_by === $r->user()->id, 403);
        $share->update(['revoked_at' => now()]);
        AuditLogger::log('document.share_revoke', $share->shareable_type, $share->shareable_id);
        return back()->with('success', 'Access revoked');
    }

    public function setPermission(Request $r)
    {
        $u = $r->user();
        $data = $r->validate([
            'permissible_type' => 'required|in:folder,document', 'permissible_id' => 'required|integer',
            'grantee_type' => 'required|in:user,department,branch', 'grantee_id' => 'required|integer',
            'caps' => 'nullable|array', 'caps.*' => 'in:'.implode(',', DocumentService::ALL_CAPS),
            'deny' => 'nullable|boolean',
        ]);
        $model = $data['permissible_type'] === 'folder' ? DocumentFolder::class : Document::class;
        $target = $model::findOrFail($data['permissible_id']);
        $authorized = $u->isAdmin() || $u->hasPermission('docs.perms')
            || ($model === Document::class && in_array('perms', DocumentService::capsOnDocument($u, $target)))
            || ($model === DocumentFolder::class && in_array('perms', DocumentService::folderCaps($u, $target)));
        abort_unless($authorized, 403);
        DocumentPermission::updateOrCreate(
            ['permissible_type' => $model, 'permissible_id' => $target->id, 'grantee_type' => $data['grantee_type'], 'grantee_id' => $data['grantee_id']],
            ['caps' => $data['caps'] ?? ['view'], 'deny' => $data['deny'] ?? false, 'granted_by' => $u->id]
        );
        AuditLogger::log('document.permission_set', class_basename($model), $target->id, null, $data);
        return back()->with('success', 'Permission saved');
    }

    public function createLink(Request $r, Document $document)
    {
        $data = $r->validate(['mode' => 'required|in:preview,download', 'password' => 'nullable|string|max:60', 'expires_at' => 'nullable|date|after:now', 'max_access' => 'nullable|integer|min:1']);
        $link = DocumentService::createLink($r->user(), $document, $data);
        return back()->with('success', 'Link created')->with('link_url', route('documents.link', $link->token));
    }

    /* -------- External secure link (public, token-gated) -------- */

    public function linkForm(string $token)
    {
        $link = DocumentLink::where('token', $token)->firstOrFail();
        abort_unless($link->isUsable(), 404);
        if ($link->password) return view('documents.link-gate', ['token' => $token]);
        return $this->serveLink($link);
    }

    public function linkAccess(Request $r, string $token)
    {
        $link = DocumentLink::where('token', $token)->firstOrFail();
        abort_unless($link->isUsable(), 404);
        abort_unless($link->password && Hash::check($r->password, $link->password), 403);
        return $this->serveLink($link);
    }

    private function serveLink(DocumentLink $link)
    {
        $link->increment('access_count');
        AuditLogger::log('document.link_access', 'Document', $link->document_id, null, ['count' => $link->access_count]);
        return DocumentService::streamVersion($link->document->current, $link->mode === 'preview');
    }

    /* ================= Document Board ================= */

    public function board(Request $r)
    {
        $u = $r->user();
        abort_unless($u->isAdmin() || $u->hasPermission('board.view'), 403);
        $announcements = DocumentService::announcementsFor($u)->with('attachments', 'publisher')->paginate(15);
        $receipts = AnnouncementReceipt::where('user_id', $u->id)->get()->keyBy('announcement_id');
        return view('documents.board', compact('announcements', 'receipts'));
    }

    public function boardShow(Request $r, Announcement $announcement)
    {
        $u = $r->user();
        $receipt = AnnouncementReceipt::where('announcement_id', $announcement->id)->where('user_id', $u->id)->firstOrFail();
        if ($receipt->status === 'delivered') $receipt->update(['status' => 'viewed', 'viewed_at' => now()]);
        $announcement->load('attachments.current', 'publisher');
        return view('documents.board-show', compact('announcement', 'receipt'));
    }

    public function acknowledge(Request $r, Announcement $announcement)
    {
        $u = $r->user();
        abort_unless($u->isAdmin() || $u->hasPermission('board.ack'), 403);
        $receipt = AnnouncementReceipt::where('announcement_id', $announcement->id)->where('user_id', $u->id)->firstOrFail();
        $receipt->update(['status' => 'acknowledged', 'acknowledged_at' => now(), 'comment' => $r->comment]);
        AuditLogger::log('announcement.ack', 'Announcement', $announcement->id);
        return back()->with('success', 'Acknowledged');
    }
}
