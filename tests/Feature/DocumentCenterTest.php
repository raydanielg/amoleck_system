<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AnnouncementReceipt;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\DocumentLink;
use App\Models\DocumentShare;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentCenterTest extends TestCase
{
    use RefreshDatabase;

    private function staff(array $perms): User
    {
        $u = User::factory()->create(['role' => User::ROLE_CLINIC_STAFF, 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);
        foreach ($perms as $p) $u->permissionOverrides()->create(['permission' => $p, 'allowed' => true]);
        return $u->fresh();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'ADMIN', 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);
    }

    public function test_module_requires_docs_view(): void
    {
        $plain = $this->staff([]);
        $this->actingAs($plain)->get(route('documents.index'))->assertForbidden();
    }

    public function test_upload_and_download(): void
    {
        Storage::fake('local');
        $u = $this->staff(['docs.view', 'docs.upload', 'docs.download']);
        $this->actingAs($u)->post(route('documents.upload'), ['files' => [UploadedFile::fake()->create('report.pdf', 50, 'application/pdf')]])->assertRedirect();
        $doc = Document::firstOrFail();
        $this->assertSame(1, $doc->current_version);
        $this->actingAs($u)->get(route('documents.download', $doc))->assertOk();
    }

    public function test_owner_only_access_to_private_docs(): void
    {
        Storage::fake('local');
        $owner = $this->staff(['docs.view', 'docs.upload']);
        $other = $this->staff(['docs.view', 'docs.download']);
        $this->actingAs($owner)->post(route('documents.upload'), ['files' => [UploadedFile::fake()->create('x.pdf', 20, 'application/pdf')]]);
        $doc = Document::firstOrFail();
        $this->actingAs($other)->get(route('documents.download', $doc))->assertForbidden();
        $this->actingAs($other)->get(route('documents.preview', $doc))->assertForbidden();
    }

    public function test_folder_permission_grant_and_deny(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $u = $this->staff(['docs.view', 'docs.download']);
        $folder = DocumentFolder::create(['name' => 'Finance', 'visibility' => 'private', 'created_by' => $admin->id, 'owner_id' => $admin->id]);
        // Grant view+preview at folder level — download NOT granted.
        \App\Models\DocumentPermission::create(['permissible_type' => DocumentFolder::class, 'permissible_id' => $folder->id, 'grantee_type' => 'user', 'grantee_id' => $u->id, 'caps' => ['view', 'preview'], 'granted_by' => $admin->id]);
        $this->actingAs($admin)->post(route('documents.upload'), ['folder_id' => $folder->id, 'files' => [UploadedFile::fake()->create('f.pdf', 10, 'application/pdf')]]);
        $doc = Document::firstOrFail();
        $this->actingAs($u)->get(route('documents.preview', $doc))->assertOk();
        $this->actingAs($u)->get(route('documents.download', $doc))->assertForbidden();
    }

    public function test_inherited_permission_and_subfolder_deny(): void
    {
        $admin = $this->admin();
        $u = $this->staff(['docs.view']);
        $parent = DocumentFolder::create(['name' => 'HR', 'visibility' => 'private', 'created_by' => $admin->id, 'owner_id' => $admin->id]);
        $child = DocumentFolder::create(['name' => 'Salaries', 'visibility' => 'private', 'parent_id' => $parent->id, 'created_by' => $admin->id, 'owner_id' => $admin->id]);
        \App\Models\DocumentPermission::create(['permissible_type' => DocumentFolder::class, 'permissible_id' => $parent->id, 'grantee_type' => 'user', 'grantee_id' => $u->id, 'caps' => ['view', 'download'], 'granted_by' => $admin->id]);
        \App\Models\DocumentPermission::create(['permissible_type' => DocumentFolder::class, 'permissible_id' => $child->id, 'grantee_type' => 'user', 'grantee_id' => $u->id, 'caps' => ['view', 'download'], 'deny' => true, 'granted_by' => $admin->id]);
        $this->assertContains('view', DocumentService::folderCaps($u, $parent));
        $this->assertNotContains('view', DocumentService::folderCaps($u, $child));
    }

    public function test_share_cannot_exceed_granters_caps(): void
    {
        Storage::fake('local');
        $owner = $this->staff(['docs.view', 'docs.upload']);
        $viewer = $this->staff(['docs.view', 'docs.share']);
        $target = $this->staff(['docs.view', 'docs.download']);
        $this->actingAs($owner)->post(route('documents.upload'), ['files' => [UploadedFile::fake()->create('s.pdf', 10, 'application/pdf')]]);
        $doc = Document::firstOrFail();
        // viewer only has view via grant
        \App\Models\DocumentPermission::create(['permissible_type' => Document::class, 'permissible_id' => $doc->id, 'grantee_type' => 'user', 'grantee_id' => $viewer->id, 'caps' => ['view', 'share'], 'granted_by' => $owner->id]);
        DocumentService::share($viewer, $doc, 'user', $target->id, ['view', 'download', 'edit']);
        $share = DocumentShare::latest()->first();
        $this->assertNotContains('edit', $share->caps); // viewer had no edit
        $this->assertNotContains('download', $share->caps); // viewer had no download
        $this->assertContains('view', $share->caps);
    }

    public function test_versions_preserved(): void
    {
        Storage::fake('local');
        $u = $this->staff(['docs.view', 'docs.upload', 'docs.versions']);
        $this->actingAs($u)->post(route('documents.upload'), ['files' => [UploadedFile::fake()->create('p.pdf', 10, 'application/pdf')]]);
        $doc = Document::firstOrFail();
        $this->actingAs($u)->post(route('documents.version', $doc), ['file' => UploadedFile::fake()->create('p2.pdf', 12, 'application/pdf'), 'note' => 'v2']);
        $doc->refresh();
        $this->assertSame(2, $doc->current_version);
        $this->assertSame(2, $doc->versions()->count());
        $this->actingAs($u)->get(route('documents.download', [$doc, 'version' => 1]))->assertOk();
    }

    public function test_trash_restore_force_delete(): void
    {
        Storage::fake('local');
        $u = $this->staff(['docs.view', 'docs.upload', 'docs.delete']);
        $this->actingAs($u)->post(route('documents.upload'), ['files' => [UploadedFile::fake()->create('t.pdf', 10, 'application/pdf')]]);
        $doc = Document::firstOrFail();
        $this->actingAs($u)->delete(route('documents.destroy', $doc))->assertRedirect();
        $this->assertSoftDeleted($doc);
        $this->actingAs($u)->post(route('documents.restore', $doc->id))->assertRedirect();
        $this->assertNull($doc->fresh()->deleted_at);
    }

    public function test_external_link_respects_expiry_and_restricted(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('documents.upload'), ['files' => [UploadedFile::fake()->create('l.pdf', 10, 'application/pdf')]]);
        $doc = Document::firstOrFail();
        $link = DocumentService::createLink($admin, $doc, ['expires_at' => now()->addDay()]);
        $this->get(route('documents.link', $link->token))->assertOk();
        $link->update(['expires_at' => now()->subHour()]);
        $this->get(route('documents.link', $link->token))->assertNotFound();
        // restricted docs cannot be linked
        $doc->update(['classification' => 'restricted']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        DocumentService::createLink($admin, $doc);
    }

    public function test_announcement_audience_and_ack(): void
    {
        $admin = $this->admin();
        $staff = $this->staff(['board.view', 'board.ack']);
        $recep = User::factory()->create(['role' => 'RECEPTIONIST', 'status' => User::STATUS_ACTIVE, 'must_change_password' => false]);
        $a = Announcement::create(['title' => 'New Policy', 'summary' => 's', 'category' => 'POLICY', 'priority' => 'high', 'status' => 'draft', 'requires_ack' => true, 'targets' => ['all' => false, 'roles' => ['CLINIC_STAFF'], 'users' => [], 'departments' => [], 'branches' => []], 'created_by' => $admin->id]);
        DocumentService::publishAnnouncement($admin, $a);
        // receipt only for targeted user
        $this->assertTrue(AnnouncementReceipt::where('user_id', $staff->id)->exists());
        $this->assertFalse(AnnouncementReceipt::where('user_id', $recep->id)->exists());
        $this->actingAs($staff)->get(route('documents.board'))->assertOk();
        $this->actingAs($staff)->get(route('documents.board.show', $a))->assertOk();
        $this->assertSame('viewed', AnnouncementReceipt::where('user_id', $staff->id)->first()->status);
        $this->actingAs($staff)->post(route('documents.board.ack', $a))->assertRedirect();
        $this->assertSame('acknowledged', AnnouncementReceipt::where('user_id', $staff->id)->first()->status);
        // untargeted user cannot open
        $this->actingAs($recep)->get(route('documents.board.show', $a))->assertNotFound();
    }

    public function test_admin_board_management(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.document-board.store'), [
            'title' => 'Staff Meeting', 'category' => 'MANAGEMENT', 'priority' => 'important',
            'target_all' => '1', 'requires_ack' => '1',
        ])->assertRedirect();
        $a = Announcement::firstOrFail();
        $this->assertSame('draft', $a->status);
        $this->actingAs($admin)->post(route('admin.document-board.publish', $a))->assertRedirect();
        $this->assertSame('published', $a->fresh()->status);
        $this->actingAs($admin)->get(route('admin.document-board.receipts', $a))->assertOk();
    }

    public function test_search_respects_permissions(): void
    {
        Storage::fake('local');
        $owner = $this->staff(['docs.view', 'docs.upload']);
        $other = $this->staff(['docs.view']);
        $this->actingAs($owner)->post(route('documents.upload'), ['files' => [UploadedFile::fake()->create('secret-payroll.pdf', 10, 'application/pdf')]]);
        $resp = $this->actingAs($other)->get(route('documents.index', ['q' => 'payroll']));
        $resp->assertOk();
        $resp->assertDontSee('secret-payroll');
    }
}
