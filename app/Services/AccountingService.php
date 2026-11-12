<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingService
{
    /** System account codes. */
    public const CASH = '1000';
    public const BANK = '1010';
    public const AR = '1100';
    public const INVENTORY = '1200';
    public const FIXED_ASSETS = '1500';
    public const ACCUM_DEP = '1510';
    public const AP = '2000';
    public const SALARY_PAYABLE = '2100';
    public const STATUTORY_PAYABLE = '2200';
    public const EQUITY = '3000';
    public const SERVICE_REVENUE = '4000';
    public const PRODUCT_REVENUE = '4100';
    public const OTHER_INCOME = '4900';
    public const COGS = '5000';
    public const SALARY_EXPENSE = '6000';
    public const EXPENSE = '6100';
    public const DEPRECIATION = '6200';

    public static function seedCoA(): void
    {
        $chart = [
            [self::CASH, 'Cash on Hand', 'asset'], [self::BANK, 'Bank Account', 'asset'],
            ['1020', 'Petty Cash', 'asset'], [self::AR, 'Accounts Receivable', 'asset'],
            [self::INVENTORY, 'Inventory', 'asset'], [self::FIXED_ASSETS, 'Fixed Assets', 'asset'],
            [self::ACCUM_DEP, 'Accumulated Depreciation', 'asset'],
            [self::AP, 'Accounts Payable', 'liability'], [self::SALARY_PAYABLE, 'Salary Payable', 'liability'],
            [self::STATUTORY_PAYABLE, 'Statutory Liabilities', 'liability'], ['2300', 'Customer Deposits', 'liability'],
            [self::EQUITY, "Owner's Capital / Retained Earnings", 'equity'],
            [self::SERVICE_REVENUE, 'Physiotherapy Service Revenue', 'income'],
            [self::PRODUCT_REVENUE, 'Medical Product Sales', 'income'],
            [self::OTHER_INCOME, 'Other Income', 'income'],
            [self::COGS, 'Cost of Goods Sold', 'expense'],
            [self::SALARY_EXPENSE, 'Salaries & Wages', 'expense'],
            [self::EXPENSE, 'Operating Expenses', 'expense'],
            [self::DEPRECIATION, 'Depreciation Expense', 'expense'],
        ];
        foreach ($chart as [$code, $name, $type]) {
            Account::firstOrCreate(['code' => $code], ['name' => $name, 'type' => $type, 'is_system' => true]);
        }
    }

    public static function account(string $code): Account
    {
        return Account::where('code', $code)->firstOrFail();
    }

    public static function ensurePeriodOpen(\DateTimeInterface|string $date): void
    {
        $d = now()->parse($date);
        $period = AccountingPeriod::where('month', $d->month)->where('year', $d->year)->first();
        if ($period && $period->status === 'closed') {
            throw ValidationException::withMessages(['period' => "Accounting period {$d->format('M Y')} is closed."]);
        }
    }

    /**
     * Post a balanced journal. Lines: [['account' => code, 'debit' => x, 'credit' => y], ...]
     * Idempotent: same source_type+source_id+reference posts only once.
     */
    public static function post(string $reference, string $description, array $lines, ?string $sourceType = null, ?int $sourceId = null, ?string $date = null, ?int $branchId = null, string $status = 'posted'): JournalEntry
    {
        $debit = round(collect($lines)->sum('debit'), 2);
        $credit = round(collect($lines)->sum('credit'), 2);
        if ($debit !== $credit || $debit <= 0) {
            throw ValidationException::withMessages(['journal' => "Journal is not balanced (DR {$debit} / CR {$credit})."]);
        }
        $date = $date ?? now()->toDateString();
        self::ensurePeriodOpen($date);

        return DB::transaction(function () use ($reference, $description, $lines, $sourceType, $sourceId, $date, $branchId, $status) {
            if ($sourceType && $sourceId) {
                $existing = JournalEntry::where('source_type', $sourceType)->where('source_id', $sourceId)->where('reference', $reference)->first();
                if ($existing) return $existing;
            }
            $entry = JournalEntry::create([
                'reference' => $reference, 'date' => $date, 'description' => $description,
                'source_type' => $sourceType, 'source_id' => $sourceId, 'status' => $status,
                'branch_id' => $branchId, 'posted_by' => auth()->id(),
            ]);
            foreach ($lines as $l) {
                $entry->lines()->create([
                    'account_id' => $l['account_id'] ?? self::account($l['account'])->id,
                    'debit' => $l['debit'] ?? 0, 'credit' => $l['credit'] ?? 0, 'memo' => $l['memo'] ?? null,
                ]);
            }
            return $entry;
        });
    }

    /** Account balance: debit-positive for asset/expense, credit-positive for others. */
    public static function balance(string $code, ?string $to = null): float
    {
        $acc = Account::where('code', $code)->first();
        if (! $acc) return 0.0;
        $q = DB::table('journal_entry_lines')->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.account_id', $acc->id)->where('journal_entries.status', 'posted');
        if ($to) $q->where('journal_entries.date', '<=', $to);
        $dr = (float) $q->sum('debit');
        $cr = (float) $q->sum('credit');
        return in_array($acc->type, ['asset', 'expense']) ? $dr - $cr : $cr - $dr;
    }

    /** Trial balance rows. */
    public static function trialBalance(?string $to = null): array
    {
        $rows = [];
        foreach (Account::where('is_active', true)->orderBy('code')->get() as $acc) {
            $q = DB::table('journal_entry_lines')->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->where('journal_entry_lines.account_id', $acc->id)->where('journal_entries.status', 'posted');
            if ($to) $q->where('journal_entries.date', '<=', $to);
            $dr = (float) $q->sum('debit'); $cr = (float) $q->sum('credit');
            if ($dr == 0 && $cr == 0) continue;
            $rows[] = ['code' => $acc->code, 'name' => $acc->name, 'type' => $acc->type, 'debit' => max(0, $dr - $cr), 'credit' => max(0, $cr - $dr)];
        }
        return $rows;
    }

    /** Profit & loss for a period. */
    public static function profitAndLoss(string $from, string $to): array
    {
        $sum = fn (string $type) => DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('accounts.type', $type)->where('journal_entries.status', 'posted')
            ->whereBetween('journal_entries.date', [$from, $to])
            ->selectRaw('SUM(debit) dr, SUM(credit) cr')->first();
        $inc = $sum('income'); $exp = $sum('expense');
        $revenue = (float) $inc->cr - (float) $inc->dr;
        $expenses = (float) $exp->dr - (float) $exp->cr;
        $cogs = DB::table('journal_entry_lines')->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')->where('accounts.code', self::COGS)
            ->where('journal_entries.status', 'posted')->whereBetween('journal_entries.date', [$from, $to])->sum('debit');
        return ['revenue' => $revenue, 'cogs' => (float) $cogs, 'gross' => $revenue - $cogs, 'expenses' => $expenses, 'net' => $revenue - $expenses];
    }
}
