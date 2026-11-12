<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // BRANCHES
        Schema::create('branches', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->string('type')->default('clinic');
            $t->string('address')->nullable();
            $t->string('region')->nullable();
            $t->string('district')->nullable();
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->string('manager')->nullable();
            $t->date('opened_at')->nullable();
            $t->string('status')->default('active'); // active | inactive
            $t->timestamps();
        });

        // HR
        Schema::create('departments', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('job_positions', function (Blueprint $t) {
            $t->id();
            $t->string('title')->unique();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->string('employee_number')->unique();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('full_name');
            $t->string('gender')->nullable();
            $t->date('dob')->nullable();
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->string('address')->nullable();
            $t->string('emergency_contact')->nullable();
            $t->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('job_position_id')->nullable()->constrained()->nullOnDelete();
            $t->string('employment_type')->default('full_time'); // full_time|part_time|contract|intern
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->date('hired_at')->nullable();
            $t->date('contract_start')->nullable();
            $t->date('contract_end')->nullable();
            $t->decimal('basic_salary', 15, 2)->default(0);
            $t->string('bank_details')->nullable(); // encrypted
            $t->string('status')->default('active'); // active|suspended|terminated|resigned
            $t->timestamps();
        });
        Schema::create('attendance_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->date('date');
            $t->time('check_in')->nullable();
            $t->time('check_out')->nullable();
            $t->string('status')->default('present'); // present|late|absent|leave|half_day
            $t->text('notes')->nullable();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['employee_id', 'date']);
        });
        Schema::create('leave_types', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->unsignedSmallInteger('default_days')->default(0);
            $t->boolean('paid')->default(true);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('leave_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->foreignId('leave_type_id')->constrained();
            $t->date('start_date');
            $t->date('end_date');
            $t->unsignedSmallInteger('days');
            $t->text('reason')->nullable();
            $t->string('status')->default('pending'); // pending|approved|rejected
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
        });

        // PAYROLL
        Schema::create('salary_components', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->string('name'); // Housing, Transport...
            $t->string('kind')->default('allowance'); // allowance|bonus
            $t->decimal('amount', 15, 2);
            $t->boolean('recurring')->default(true);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('payroll_rules', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('applies_to')->default('employee'); // employee deduction | employer contribution
            $t->string('calc_method')->default('fixed'); // fixed|percent_gross|percent_basic
            $t->decimal('value', 15, 4)->default(0);
            $t->date('effective_from')->nullable();
            $t->date('effective_to')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('employee_loans', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->string('kind')->default('loan'); // loan|advance
            $t->decimal('amount', 15, 2);
            $t->decimal('monthly_installment', 15, 2)->nullable();
            $t->decimal('balance', 15, 2);
            $t->string('status')->default('approved'); // pending|approved|closed
            $t->date('issued_at')->nullable();
            $t->text('reason')->nullable();
            $t->timestamps();
        });
        Schema::create('loan_repayments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_loan_id')->constrained()->cascadeOnDelete();
            $t->decimal('amount', 15, 2);
            $t->date('paid_at');
            $t->string('source')->default('payroll'); // payroll|manual
            $t->unsignedBigInteger('payroll_item_id')->nullable();
            $t->timestamps();
        });
        Schema::create('payroll_batches', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->unsignedTinyInteger('month');
            $t->unsignedSmallInteger('year');
            $t->string('status')->default('draft'); // draft|approved|paid|closed
            $t->decimal('total_gross', 15, 2)->default(0);
            $t->decimal('total_deductions', 15, 2)->default(0);
            $t->decimal('total_net', 15, 2)->default(0);
            $t->decimal('total_employer', 15, 2)->default(0);
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
            $t->unique(['month', 'year']);
        });
        Schema::create('payroll_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payroll_batch_id')->constrained()->cascadeOnDelete();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->decimal('basic', 15, 2)->default(0);
            $t->decimal('allowances', 15, 2)->default(0);
            $t->decimal('gross', 15, 2)->default(0);
            $t->json('deductions')->nullable(); // [{name,amount}]
            $t->decimal('total_deductions', 15, 2)->default(0);
            $t->decimal('net', 15, 2)->default(0);
            $t->json('employer_contributions')->nullable();
            $t->decimal('total_employer', 15, 2)->default(0);
            $t->decimal('paid', 15, 2)->default(0);
            $t->string('payment_status')->default('unpaid'); // unpaid|partial|paid
            $t->timestamps();
            $t->unique(['payroll_batch_id', 'employee_id']);
        });
        Schema::create('payroll_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();
            $t->decimal('amount', 15, 2);
            $t->date('paid_at');
            $t->string('method')->default('bank');
            $t->string('reference')->nullable();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        // ASSETS
        Schema::create('asset_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('assets', function (Blueprint $t) {
            $t->id();
            $t->string('asset_number')->unique();
            $t->string('name');
            $t->foreignId('asset_category_id')->nullable()->constrained()->nullOnDelete();
            $t->text('description')->nullable();
            $t->string('serial_number')->nullable();
            $t->string('model')->nullable();
            $t->string('brand')->nullable();
            $t->date('purchased_at')->nullable();
            $t->decimal('purchase_cost', 15, 2)->nullable();
            $t->string('supplier_name')->nullable();
            $t->date('warranty_expires')->nullable();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $t->string('condition')->default('good'); // new|good|fair|poor
            $t->string('status')->default('active'); // active|maintenance|disposed
            $t->string('depreciation_method')->default('straight_line'); // straight_line|declining
            $t->unsignedSmallInteger('useful_life_years')->nullable();
            $t->decimal('residual_value', 15, 2)->default(0);
            $t->date('disposed_at')->nullable();
            $t->decimal('disposal_value', 15, 2)->nullable();
            $t->string('disposal_reason')->nullable();
            $t->timestamps();
        });
        Schema::create('asset_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $t->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action'); // assigned|returned|transferred
            $t->string('condition')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('asset_maintenance', function (Blueprint $t) {
            $t->id();
            $t->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $t->string('type')->default('repair'); // repair|service|inspection
            $t->string('provider')->nullable();
            $t->date('performed_at')->nullable();
            $t->date('next_due')->nullable();
            $t->decimal('cost', 15, 2)->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        // INVENTORY BATCHES
        Schema::create('inventory_batches', function (Blueprint $t) {
            $t->id();
            $t->string('batch_number');
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $t->date('manufactured_at')->nullable();
            $t->date('expires_at')->nullable();
            $t->unsignedInteger('qty_received');
            $t->unsignedInteger('qty_available');
            $t->decimal('unit_cost', 15, 2)->nullable();
            $t->string('location')->nullable();
            $t->date('received_at')->nullable();
            $t->string('status')->default('active'); // active|quarantined|expired|depleted
            $t->timestamps();
            $t->unique(['product_id', 'batch_number']);
            $t->index(['expires_at', 'status']);
        });

        // BUDGETS & PETTY CASH
        Schema::create('expense_budgets', function (Blueprint $t) {
            $t->id();
            $t->unsignedTinyInteger('month');
            $t->unsignedSmallInteger('year');
            $t->string('category');
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->decimal('amount', 15, 2);
            $t->timestamps();
            $t->unique(['month', 'year', 'category', 'branch_id']);
        });
        Schema::create('petty_cash_transactions', function (Blueprint $t) {
            $t->id();
            $t->string('type'); // allocation|expense|replenishment
            $t->decimal('amount', 15, 2);
            $t->string('description');
            $t->date('date');
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        // branch_id on key existing tables
        Schema::table('patients', fn (Blueprint $t) => $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete());
        Schema::table('invoices', fn (Blueprint $t) => $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete());
        Schema::table('sales', fn (Blueprint $t) => $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete());
        Schema::table('expenses', fn (Blueprint $t) => $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete());
    }

    public function down(): void
    {
        Schema::table('patients', fn (Blueprint $t) => $t->dropConstrainedForeignId('branch_id'));
        Schema::table('invoices', fn (Blueprint $t) => $t->dropConstrainedForeignId('branch_id'));
        Schema::table('sales', fn (Blueprint $t) => $t->dropConstrainedForeignId('branch_id'));
        Schema::table('expenses', fn (Blueprint $t) => $t->dropConstrainedForeignId('branch_id'));
        foreach (['petty_cash_transactions', 'expense_budgets', 'inventory_batches', 'asset_maintenance', 'asset_assignments', 'assets', 'asset_categories', 'payroll_payments', 'payroll_items', 'payroll_batches', 'loan_repayments', 'employee_loans', 'payroll_rules', 'salary_components', 'leave_requests', 'leave_types', 'attendance_records', 'employees', 'job_positions', 'departments', 'branches'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
