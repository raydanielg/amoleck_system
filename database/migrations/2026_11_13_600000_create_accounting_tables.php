<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('type'); // asset|liability|equity|income|expense
            $t->boolean('is_system')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('financial_accounts', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('kind')->default('cash'); // cash|bank|petty
            $t->string('bank_name')->nullable();
            $t->string('account_number')->nullable();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->decimal('opening_balance', 15, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('accounting_periods', function (Blueprint $t) {
            $t->id();
            $t->unsignedTinyInteger('month');
            $t->unsignedSmallInteger('year');
            $t->string('status')->default('open'); // open|closed
            $t->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('closed_at')->nullable();
            $t->timestamps();
            $t->unique(['month', 'year']);
        });
        Schema::create('journal_entries', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->date('date');
            $t->string('description');
            $t->string('source_type')->nullable(); // payment|expense|sale|payroll|invoice|manual
            $t->unsignedBigInteger('source_id')->nullable();
            $t->string('status')->default('posted'); // draft|posted
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['source_type', 'source_id']);
        });
        Schema::create('journal_entry_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $t->foreignId('account_id')->constrained();
            $t->decimal('debit', 15, 2)->default(0);
            $t->decimal('credit', 15, 2)->default(0);
            $t->string('memo')->nullable();
            $t->timestamps();
        });
        Schema::create('bank_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('financial_account_id')->constrained()->cascadeOnDelete();
            $t->date('date');
            $t->string('reference')->nullable();
            $t->string('description')->nullable();
            $t->decimal('amount', 15, 2); // + deposit / - withdrawal
            $t->string('status')->default('unmatched'); // unmatched|matched|reconciled|ignored
            $t->timestamps();
            $t->index(['status', 'financial_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounting_periods');
        Schema::dropIfExists('financial_accounts');
        Schema::dropIfExists('accounts');
    }
};
