<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('type', 20)->default('service')->after('invoice_reference'); // service, product, combined
            $table->date('due_date')->nullable()->after('issue_date');
            $table->text('terms')->nullable();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('item_type', 10)->default('service')->after('invoice_id'); // service, product
            $table->foreignId('product_id')->nullable()->after('service_id')->constrained()->nullOnDelete();
            $table->decimal('unit_cost', 14, 2)->default(0)->after('unit_price');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('transaction_reference')->nullable()->after('payment_method');
            $table->string('verification_status', 20)->default('verified')->after('status'); // verified, pending, rejected
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('proof_note')->nullable();
        });

        Schema::create('service_fulfilments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('requested'); // requested, scheduled, preparing, ready, in_progress, completed, closed
            $table->date('scheduled_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status']);
        });

        Schema::create('product_fulfilments', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_reference')->unique();
            $table->foreignId('invoice_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('received'); // received, payment_verified, stock_confirmed, picking, quality_check, packaging, ready_dispatch, dispatched, delivered, completed
            $table->string('delivery_method')->nullable(); // collection, clinic_delivery, courier, other
            $table->text('delivery_address')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->string('courier')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('proof_note')->nullable(); // proof of delivery / collection confirmation
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status']);
        });

        Schema::create('fulfilment_events', function (Blueprint $table) {
            $table->id();
            $table->string('fulfilment_type', 10); // service, product
            $table->unsignedBigInteger('fulfilment_id');
            $table->string('step', 40);
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['fulfilment_type', 'fulfilment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfilment_events');
        Schema::dropIfExists('product_fulfilments');
        Schema::dropIfExists('service_fulfilments');
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['transaction_reference', 'verification_status', 'verified_at', 'proof_note']);
        });
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn(['item_type', 'unit_cost']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['type', 'due_date', 'terms']);
        });
    }
};
