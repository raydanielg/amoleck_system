<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // per-user permission overrides (allowed = granted/revoked vs role default)
        Schema::create('user_permissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('permission');
            $t->boolean('allowed')->default(true);
            $t->timestamps();
            $t->unique(['user_id', 'permission']);
        });

        // front desk queue
        Schema::create('patient_queue', function (Blueprint $t) {
            $t->id();
            $t->string('queue_number');
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->string('service_label')->nullable();
            $t->boolean('is_walkin')->default(false);
            $t->string('status')->default('waiting'); // waiting|called|in_service|completed|cancelled|no_show
            $t->timestamp('checked_in_at')->nullable();
            $t->timestamp('called_at')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['status', 'created_at']);
            $t->unique(['queue_number', 'created_at'], 'queue_num_day');
        });

        // generic HR requests (leave correction, info update, docs...)
        Schema::create('hr_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->string('type'); // info_update|attendance_correction|document|other
            $t->string('subject');
            $t->text('details')->nullable();
            $t->string('status')->default('submitted'); // submitted|under_review|approved|rejected|completed
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->text('decision_reason')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_requests');
        Schema::dropIfExists('patient_queue');
        Schema::dropIfExists('user_permissions');
    }
};
