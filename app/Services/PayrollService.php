<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\LoanRepayment;
use App\Models\PayrollBatch;
use App\Models\PayrollItem;
use App\Models\PayrollPayment;
use App\Models\PayrollRule;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    /** Calculate one employee's payroll breakdown for a period. */
    public static function calculate(Employee $employee, int $month, int $year): array
    {
        $periodStart = now()->setYear($year)->setMonth($month)->startOfMonth()->toDateString();

        $basic = (float) $employee->basic_salary;
        $allowances = (float) $employee->components()->where('is_active', true)->where('kind', 'allowance')->sum('amount');
        $bonuses = (float) $employee->components()->where('is_active', true)->where('kind', 'bonus')->sum('amount');
        $gross = $basic + $allowances + $bonuses;

        $deductions = [];
        $employerContribs = [];

        foreach (PayrollRule::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', $periodStart))
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $periodStart))
            ->get() as $rule) {
            $base = $rule->calc_method === 'percent_basic' ? $basic : $gross;
            $amount = $rule->calc_method === 'fixed' ? (float) $rule->value : round($base * (float) $rule->value / 100, 2);
            if ($amount <= 0) continue;
            if ($rule->applies_to === 'employer') {
                $employerContribs[] = ['name' => $rule->name, 'amount' => $amount];
            } else {
                $deductions[] = ['name' => $rule->name, 'amount' => $amount];
            }
        }

        // Loan installments — capped at remaining balance
        foreach ($employee->loans()->where('status', 'approved')->where('balance', '>', 0)->get() as $loan) {
            $installment = min((float) ($loan->monthly_installment ?? $loan->balance), (float) $loan->balance);
            if ($installment > 0) {
                $deductions[] = ['name' => "Loan repayment {$loan->reference}", 'amount' => $installment, 'loan_id' => $loan->id];
            }
        }

        $totalDeductions = round(collect($deductions)->sum('amount'), 2);
        $net = round($gross - $totalDeductions, 2);
        $totalEmployer = round(collect($employerContribs)->sum('amount'), 2);

        return [
            'basic' => $basic, 'allowances' => $allowances + $bonuses, 'gross' => $gross,
            'deductions' => $deductions, 'total_deductions' => $totalDeductions,
            'net' => $net, 'employer_contributions' => $employerContribs, 'total_employer' => $totalEmployer,
        ];
    }

    /** Create a draft payroll batch with items for all active employees. */
    public static function createBatch(int $month, int $year): PayrollBatch
    {
        return DB::transaction(function () use ($month, $year) {
            if (PayrollBatch::where('month', $month)->where('year', $year)->exists()) {
                throw ValidationException::withMessages(['month' => 'A payroll batch already exists for this period.']);
            }
            $batch = PayrollBatch::create([
                'reference' => sprintf('PAY-%d-%02d-%03d', $year, $month, PayrollBatch::where('year', $year)->count() + 1),
                'month' => $month, 'year' => $year, 'status' => 'draft',
            ]);
            foreach (Employee::where('status', 'active')->get() as $emp) {
                $batch->items()->create(array_merge(['employee_id' => $emp->id], self::calculate($emp, $month, $year)));
            }
            self::recalcTotals($batch);
            AuditLogger::log('payroll.created', 'payroll_batch', $batch->id);
            return $batch->fresh();
        });
    }

    public static function recalcTotals(PayrollBatch $batch): void
    {
        $batch->update([
            'total_gross' => $batch->items()->sum('gross'),
            'total_deductions' => $batch->items()->sum('total_deductions'),
            'total_net' => $batch->items()->sum('net'),
            'total_employer' => $batch->items()->sum('total_employer'),
        ]);
    }

    public static function approve(PayrollBatch $batch, int $userId): PayrollBatch
    {
        $batch->refresh();
        if ($batch->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only draft payroll can be approved.']);
        }
        $batch->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now()]);
        AuditLogger::log('payroll.approved', 'payroll_batch', $batch->id);
        try {
            \App\Services\AccountingService::post('PAYROLL-'.$batch->reference, 'Payroll '.$batch->reference.' approved', [
                ['account' => \App\Services\AccountingService::SALARY_EXPENSE, 'debit' => (float) $batch->total_gross + (float) $batch->total_employer],
                ['account' => \App\Services\AccountingService::SALARY_PAYABLE, 'credit' => $batch->total_net],
                ['account' => \App\Services\AccountingService::STATUTORY_PAYABLE, 'credit' => (float) $batch->total_deductions + (float) $batch->total_employer],
            ], 'payroll', $batch->id);
        } catch (\Throwable) {}
        return $batch;
    }

    /** Record a salary payment against an item. Batch must be approved (or already partially paid). */
    public static function recordPayment(PayrollItem $item, float $amount, string $method, ?string $reference, int $userId): PayrollPayment
    {
        return DB::transaction(function () use ($item, $amount, $method, $reference, $userId) {
            $batch = $item->batch()->lockForUpdate()->first();
            if (! in_array($batch->status, ['approved', 'paid'])) {
                throw ValidationException::withMessages(['status' => 'Payroll must be approved before recording payments.']);
            }
            $item = $batch->items()->lockForUpdate()->find($item->id);
            $outstanding = (float) $item->net - (float) $item->paid;
            if ($amount <= 0 || $amount > $outstanding) {
                throw ValidationException::withMessages(['amount' => 'Amount must be between 0 and the outstanding balance ('.number_format($outstanding, 2).').']);
            }
            $payment = $item->payments()->create([
                'amount' => $amount, 'paid_at' => now(), 'method' => $method,
                'reference' => $reference, 'recorded_by' => $userId,
            ]);
            $paid = (float) $item->paid + $amount;
            $item->update(['paid' => $paid, 'payment_status' => $paid >= (float) $item->net - 0.005 ? 'paid' : 'partial']);

            // post loan repayments captured at calculation time
            foreach ($item->deductions ?? [] as $ded) {
                if (! empty($ded['loan_id']) && ! ($ded['posted'] ?? false)) {
                    $loan = EmployeeLoan::lockForUpdate()->find($ded['loan_id']);
                    if ($loan && $loan->status === 'approved') {
                        $loan->decrement('balance', $ded['amount']);
                        LoanRepayment::create(['employee_loan_id' => $loan->id, 'amount' => $ded['amount'], 'paid_at' => now(), 'source' => 'payroll', 'payroll_item_id' => $item->id]);
                        if ($loan->fresh()->balance <= 0) $loan->update(['status' => 'closed']);
                    }
                }
            }

            if ($batch->items()->where('payment_status', '!=', 'paid')->doesntExist()) {
                $batch->update(['status' => 'paid']);
            }
            AuditLogger::log('payroll.payment', 'payroll_payment', $payment->id);
            try {
                $m = $method === 'cash' ? \App\Services\AccountingService::CASH : \App\Services\AccountingService::BANK;
                \App\Services\AccountingService::post('SAL-'.$payment->id, 'Salary payment '.$item->employee->employee_number, [
                    ['account' => \App\Services\AccountingService::SALARY_PAYABLE, 'debit' => $amount],
                    ['account' => $m, 'credit' => $amount],
                ], 'salary_payment', $payment->id);
            } catch (\Throwable) {}
            return $payment;
        });
    }

    public static function close(PayrollBatch $batch): PayrollBatch
    {
        $batch->refresh();
        if ($batch->status !== 'paid') {
            throw ValidationException::withMessages(['status' => 'Only fully paid payroll can be closed.']);
        }
        $batch->update(['status' => 'closed']);
        AuditLogger::log('payroll.closed', 'payroll_batch', $batch->id);
        return $batch;
    }
}
