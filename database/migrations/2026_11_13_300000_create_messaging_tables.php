<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('generic'); // adapter key
            $table->string('label')->nullable();
            $table->string('base_url')->nullable();
            $table->string('endpoint')->nullable();
            $table->text('api_key')->nullable(); // encrypted
            $table->text('api_secret')->nullable(); // encrypted
            $table->string('username')->nullable();
            $table->text('password')->nullable(); // encrypted
            $table->string('sender_id')->nullable();
            $table->string('country_code', 8)->default('+255');
            $table->unsignedInteger('timeout_seconds')->default(15);
            $table->string('webhook_secret')->nullable();
            $table->boolean('enabled')->default(false);
            $table->decimal('cost_per_segment', 10, 4)->nullable();
            $table->decimal('manual_balance', 14, 2)->nullable();
            $table->timestamp('balance_checked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('category')->default('general');
            $table->text('body');
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_reference')->unique();
            $table->string('idempotency_key')->unique();
            $table->foreignId('template_id')->nullable()->constrained('message_templates')->nullOnDelete();
            $table->string('event_key')->nullable(); // e.g. payment.recorded
            $table->string('type', 30)->default('manual'); // manual, automatic, campaign, test
            $table->string('recipient_name')->nullable();
            $table->string('phone');
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->unsignedTinyInteger('segments')->default(1);
            $table->decimal('cost', 12, 4)->nullable();
            $table->string('status', 20)->default('queued'); // draft, queued, scheduled, submitted, sent, delivered, failed, cancelled, unknown
            $table->string('provider')->nullable();
            $table->string('provider_ref')->nullable();
            $table->text('provider_response')->nullable();
            $table->string('failure_reason')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('campaign_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['phone']);
            $table->index(['event_key']);
        });

        Schema::create('message_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20);
            $table->text('detail')->nullable();
            $table->timestamps();

            $table->index(['message_id']);
        });

        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique();
            $table->string('label');
            $table->foreignId('template_id')->nullable()->constrained('message_templates')->nullOnDelete();
            $table->string('recipient_source')->default('patient'); // patient, customer, admins
            $table->boolean('enabled')->default(false);
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->timestamps();
        });

        Schema::create('message_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('body');
            $table->foreignId('template_id')->nullable()->constrained('message_templates')->nullOnDelete();
            $table->string('audience')->default('all_patients'); // all_patients, recent_patients, followups, customers, outstanding
            $table->string('status', 20)->default('draft'); // draft, scheduled, sending, sent, cancelled
            $table->unsignedInteger('recipients_count')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('communication_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone')->nullable();
            $table->boolean('sms_opt_out')->default(false);
            $table->timestamps();
            $table->unique(['patient_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_consents');
        Schema::dropIfExists('message_campaigns');
        Schema::dropIfExists('notification_rules');
        Schema::dropIfExists('message_events');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('sms_gateways');
    }
};
