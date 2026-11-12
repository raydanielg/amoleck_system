<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('visit_reference')->unique();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->dateTime('visit_datetime');
            $table->text('chief_complaint')->nullable();
            $table->text('assessment_notes')->nullable();
            $table->text('treatment_notes')->nullable();
            $table->text('plan_notes')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('completed_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['visit_datetime']);
            $table->index(['status']);
            $table->index(['patient_id', 'visit_datetime']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
