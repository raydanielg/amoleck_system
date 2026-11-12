<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_reference')->unique();
            $table->string('title');
            $table->foreignId('expense_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category_name_snapshot')->nullable();
            $table->decimal('amount', 14, 2);
            $table->date('expense_date');
            $table->text('description')->nullable();
            $table->string('payee')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('status', 20)->default('submitted');
            $table->text('rejection_reason')->nullable();
            $table->text('void_reason')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['expense_date']);
            $table->index(['status']);
            $table->index(['expense_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
