<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('lead_sources')->insert(collect([
            'Facebook', 'Instagram', 'TikTok', 'WhatsApp', 'Website',
            'Google Ads', 'Referral', 'Walk-in', 'Other',
        ])->map(fn ($name, $i) => [
            'name' => $name, 'is_active' => true, 'sort_order' => $i,
            'created_at' => now(), 'updated_at' => now(),
        ])->all());

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_reference')->unique();
            $table->string('full_name');
            $table->string('phone')->index();
            $table->string('email')->nullable();
            $table->foreignId('lead_source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->string('campaign_name')->nullable();
            $table->string('ad_name')->nullable();
            $table->string('platform')->nullable();
            $table->foreignId('interested_service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->text('description')->nullable();
            $table->text('initial_notes')->nullable();
            $table->date('received_at')->index();

            $table->string('contact_status')->default('new')->index();       // new | contacted | not_contacted
            $table->string('interest_level')->nullable();                   // interested | not_interested | needs_info
            $table->string('visit_status')->default('expected')->index();   // expected | confirmed | visited | service_received | follow_up_required | not_converted
            $table->date('expected_visit_at')->nullable();
            $table->date('next_followup_at')->nullable()->index();

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->index();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();

            $table->foreignId('converted_patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lead_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('contacted_at');
            $table->boolean('reached')->default(true);
            $table->string('outcome')->nullable();
            $table->string('interest_level')->nullable();
            $table->text('notes')->nullable();
            $table->date('next_followup_at')->nullable();
            $table->timestamps();
            $table->index(['lead_id', 'contacted_at']);
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // created | edited | assigned | status_change | follow_up | note | converted
            $table->text('description');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['lead_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('lead_follow_ups');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('lead_sources');
    }
};
