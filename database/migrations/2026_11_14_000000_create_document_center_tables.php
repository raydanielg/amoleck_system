<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_folders', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code')->nullable();
            $t->foreignId('parent_id')->nullable()->constrained('document_folders')->nullOnDelete();
            $t->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('description')->nullable();
            $t->string('category')->nullable();
            $t->string('visibility')->default('private'); // private | department | branch | org
            $t->string('status')->default('active'); // active | archived
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
            $t->index(['parent_id', 'visibility']);
        });

        Schema::create('documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('folder_id')->nullable()->constrained('document_folders')->nullOnDelete();
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('reference')->nullable();
            $t->string('category')->nullable();
            $t->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('owner_id')->constrained('users');
            $t->unsignedInteger('current_version')->default(0);
            $t->string('status')->default('active'); // active | pending_approval | approved | published | archived
            $t->string('classification')->default('internal'); // internal | confidential | restricted
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
            $t->softDeletes();
            $t->index(['folder_id', 'status']);
            $t->index(['department_id', 'branch_id']);
        });

        Schema::create('document_versions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('document_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('version');
            $t->string('path');
            $t->string('original_name');
            $t->string('mime')->nullable();
            $t->unsignedBigInteger('size')->default(0);
            $t->foreignId('uploaded_by')->constrained('users');
            $t->text('note')->nullable();
            $t->timestamps();
            $t->unique(['document_id', 'version']);
        });

        Schema::create('document_permissions', function (Blueprint $t) {
            $t->id();
            $t->string('permissible_type'); // App\Models\DocumentFolder | App\Models\Document
            $t->unsignedBigInteger('permissible_id');
            $t->string('grantee_type'); // user | department | branch
            $t->unsignedBigInteger('grantee_id');
            $t->json('caps'); // e.g. ["view","preview","download"]
            $t->boolean('deny')->default(false);
            $t->foreignId('granted_by')->constrained('users');
            $t->timestamps();
            $t->unique(['permissible_type', 'permissible_id', 'grantee_type', 'grantee_id'], 'docperm_unique');
            $t->index(['grantee_type', 'grantee_id']);
        });

        Schema::create('document_shares', function (Blueprint $t) {
            $t->id();
            $t->string('shareable_type'); // Document | DocumentFolder
            $t->unsignedBigInteger('shareable_id');
            $t->foreignId('shared_by')->constrained('users');
            $t->string('grantee_type'); // user | department | branch
            $t->unsignedBigInteger('grantee_id');
            $t->json('caps');
            $t->text('reason')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();
            $t->index(['grantee_type', 'grantee_id']);
        });

        Schema::create('document_links', function (Blueprint $t) {
            $t->id();
            $t->foreignId('document_id')->constrained()->cascadeOnDelete();
            $t->string('token', 64)->unique();
            $t->foreignId('created_by')->constrained('users');
            $t->string('mode')->default('download'); // preview | download
            $t->string('password')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->unsignedInteger('max_access')->nullable();
            $t->unsignedInteger('access_count')->default(0);
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();
        });

        Schema::create('document_stars', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('document_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['user_id', 'document_id']);
        });

        Schema::create('announcements', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('summary')->nullable();
            $t->text('body')->nullable();
            $t->string('category')->default('GENERAL');
            $t->string('priority')->default('normal'); // normal | important | high | critical
            $t->string('status')->default('draft'); // draft | scheduled | published | archived
            $t->boolean('pinned')->default(false);
            $t->boolean('requires_ack')->default(false);
            $t->json('targets')->nullable(); // {all,roles[],departments[],branches[],users[]}
            $t->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('published_at')->nullable();
            $t->timestamp('scheduled_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
            $t->index(['status', 'pinned']);
        });

        Schema::create('announcement_attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $t->foreignId('document_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['announcement_id', 'document_id']);
        });

        Schema::create('announcement_receipts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestamp('delivered_at')->nullable();
            $t->timestamp('viewed_at')->nullable();
            $t->timestamp('acknowledged_at')->nullable();
            $t->string('status')->default('delivered'); // delivered | viewed | acknowledged | declined
            $t->string('comment')->nullable();
            $t->timestamps();
            $t->unique(['announcement_id', 'user_id']);
            $t->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['announcement_receipts', 'announcement_attachments', 'announcements', 'document_stars', 'document_links', 'document_shares', 'document_permissions', 'document_versions', 'documents', 'document_folders'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
