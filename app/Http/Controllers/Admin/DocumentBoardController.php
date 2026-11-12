<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementReceipt;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DocumentService;
use Illuminate\Http\Request;

class DocumentBoardController extends Controller
{
    private function auth(Request $r, string $perm = 'board.publish'): void
    {
        abort_unless($r->user()->isAdmin() || $r->user()->hasPermission($perm), 403);
    }

    public function index(Request $r)
    {
        abort_unless($r->user()->isAdmin() || $r->user()->hasPermission('board.manage') || $r->user()->hasPermission('board.publish'), 403);
        $announcements = Announcement::latest()->paginate(25);
        return view('admin.documents.board', [
            'announcements' => $announcements,
            'departments' => Department::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'users' => User::where('status', User::STATUS_ACTIVE)->orderBy('name')->get(),
            'categories' => ['GENERAL', 'MANAGEMENT', 'HR', 'FINANCE', 'CLINICAL', 'RECEPTION', 'OPERATIONS', 'TRAINING', 'POLICY', 'CIRCULAR', 'EMERGENCY'],
        ]);
    }

    public function store(Request $r)
    {
        $this->auth($r);
        $data = $r->validate([
            'title' => 'required|string|max:200', 'summary' => 'nullable|string|max:300',
            'body' => 'nullable|string|max:20000', 'category' => 'required|string|max:40',
            'priority' => 'required|in:normal,important,high,critical',
            'pinned' => 'nullable|boolean', 'requires_ack' => 'nullable|boolean',
            'expires_at' => 'nullable|date|after:now', 'scheduled_at' => 'nullable|date|after:now',
            'target_all' => 'nullable|boolean',
            'roles' => 'nullable|array', 'roles.*' => 'in:ADMIN,RECEPTIONIST,CLINIC_STAFF',
            'departments' => 'nullable|array', 'departments.*' => 'integer',
            'branches' => 'nullable|array', 'branches.*' => 'integer',
            'users' => 'nullable|array', 'users.*' => 'integer',
            'document_ids' => 'nullable|array', 'document_ids.*' => 'exists:documents,id',
            'files.*' => DocumentService::uploadRules()['file'],
        ]);
        $targets = ['all' => ! empty($data['target_all']), 'roles' => $data['roles'] ?? [], 'departments' => $data['departments'] ?? [], 'branches' => $data['branches'] ?? [], 'users' => $data['users'] ?? []];
        $a = Announcement::create($r->only('title', 'summary', 'body', 'category', 'priority') + [
            'pinned' => ! empty($data['pinned']), 'requires_ack' => ! empty($data['requires_ack']),
            'expires_at' => $data['expires_at'] ?? null, 'scheduled_at' => $data['scheduled_at'] ?? null,
            'targets' => $targets,
            'status' => ! empty($data['scheduled_at']) ? 'scheduled' : 'draft',
            'created_by' => $r->user()->id,
        ]);
        // Attachments: existing documents or fresh uploads (into "Board Attachments" folder)
        foreach ($data['document_ids'] ?? [] as $docId) $a->attachments()->syncWithoutDetaching([$docId]);
        foreach ($r->file('files', []) as $file) {
            $folder = DocumentFolder::firstOrCreate(['code' => 'BOARD'], ['name' => 'Board Attachments', 'visibility' => 'private', 'created_by' => $r->user()->id, 'owner_id' => $r->user()->id]);
            $doc = DocumentService::storeFile($r->user(), $file, $folder, ['category' => 'board']);
            $a->attachments()->syncWithoutDetaching([$doc->id]);
        }
        AuditLogger::log('announcement.create', 'Announcement', $a->id, null, ['title' => $a->title]);
        return back()->with('success', $a->status === 'scheduled' ? 'Announcement scheduled' : 'Draft saved');
    }

    public function update(Request $r, Announcement $announcement)
    {
        abort_unless($r->user()->isAdmin() || $r->user()->hasPermission('board.manage'), 403);
        $data = $r->validate(['title' => 'sometimes|string|max:200', 'summary' => 'nullable|string|max:300', 'body' => 'nullable|string|max:20000', 'category' => 'nullable|string|max:40', 'priority' => 'nullable|in:normal,important,high,critical', 'pinned' => 'nullable|boolean', 'requires_ack' => 'nullable|boolean', 'expires_at' => 'nullable|date']);
        $announcement->update($data);
        AuditLogger::log('announcement.update', 'Announcement', $announcement->id, null, $data);
        return back()->with('success', 'Updated');
    }

    public function publish(Request $r, Announcement $announcement)
    {
        $this->auth($r);
        abort_if($announcement->status === 'published', 422);
        DocumentService::publishAnnouncement($r->user(), $announcement);
        return back()->with('success', 'Published to audience');
    }

    public function archive(Request $r, Announcement $announcement)
    {
        abort_unless($r->user()->isAdmin() || $r->user()->hasPermission('board.manage'), 403);
        $announcement->update(['status' => 'archived']);
        AuditLogger::log('announcement.archive', 'Announcement', $announcement->id);
        return back()->with('success', 'Archived');
    }

    public function receipts(Request $r, Announcement $announcement)
    {
        abort_unless($r->user()->isAdmin() || $r->user()->hasPermission('board.reports'), 403);
        $receipts = $announcement->receipts()->with('user.employee.department')->latest()->get();
        return view('admin.documents.receipts', compact('announcement', 'receipts'));
    }

    public function remind(Request $r, Announcement $announcement)
    {
        abort_unless($r->user()->isAdmin() || $r->user()->hasPermission('board.manage'), 403);
        $pending = $announcement->receipts()->whereIn('status', ['delivered', 'viewed'])->with('user')->get();
        foreach ($pending as $receipt) {
            \App\Services\NotificationService::notify($receipt->user, 'announcement.reminder', 'Reminder: '.$announcement->title, 'Please review and acknowledge this notice.', route('documents.board'));
        }
        AuditLogger::log('announcement.remind', 'Announcement', $announcement->id, null, ['count' => $pending->count()]);
        return back()->with('success', 'Reminders sent to '.$pending->count().' employee(s)');
    }
}
