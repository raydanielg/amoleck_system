<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\PayrollBatch;
use App\Services\AccountingService;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingController extends Controller
{
    public function dashboard(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        return view('accounting.dashboard', [
            'pnl' => AccountingService::profitAndLoss($from, $to),
            'balances' => [
                'cash' => AccountingService::balance(AccountingService::CASH, $to),
                'bank' => AccountingService::balance(AccountingService::BANK, $to),
                'receivable' => AccountingService::balance(AccountingService::AR, $to),
                'payable' => AccountingService::balance(AccountingService::AP, $to),
                'salary_payable' => AccountingService::balance(AccountingService::SALARY_PAYABLE, $to),
            ],
            'ar' => $this->receivables(),
            'pendingVerification' => DB::table('payments')->where('verification_status', 'pending')->count(),
            'from' => $from, 'to' => $to,
        ]);
    }

    private function receivables(): array
    {
        $rows = Invoice::whereIn('status', ['issued', 'partially_paid'])->where('balance', '>', 0)->get();
        $buckets = ['current' => 0, '30' => 0, '60' => 0, '90' => 0, '90+' => 0];
        foreach ($rows as $inv) {
            $days = $inv->due_date ? $inv->due_date->diffInDays(now(), false) : 0;
            $key = $days <= 0 ? 'current' : ($days <= 30 ? '30' : ($days <= 60 ? '60' : ($days <= 90 ? '90' : '90+')));
            $buckets[$key] += (float) $inv->balance;
        }
        return ['buckets' => $buckets, 'invoices' => $rows->sortByDesc('balance')->take(15)];
    }

    public function journals(Request $request)
    {
        return view('accounting.journals', [
            'entries' => JournalEntry::with('lines.account')->latest('date')->latest('id')->paginate(25),
            'accounts' => Account::where('is_active', true)->orderBy('code')->get(),
            'periods' => AccountingPeriod::latest('year')->latest('month')->get(),
        ]);
    }

    public function storeJournal(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('accounting.journals'), 403);
        $data = $request->validate([
            'date' => 'required|date', 'description' => 'required|string|max:255',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
        ]);
        $lines = collect($data['lines'])->map(fn ($l) => ['account_id' => $l['account_id'], 'debit' => (float) ($l['debit'] ?? 0), 'credit' => (float) ($l['credit'] ?? 0)])->all();
        $entry = AccountingService::post(
            'JE-'.now()->format('Ymd').'-'.str_pad(JournalEntry::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
            $data['description'], $lines, 'manual', null, $data['date']
        );
        AuditLogger::log('accounting.journal_posted', 'journal_entry', $entry->id);
        return back()->with('success', "Journal {$entry->reference} posted.");
    }

    public function reports(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('accounting.reports'), 403);
        $from = $request->get('from', now()->startOfYear()->toDateString());
        $to = $request->get('to', now()->toDateString());
        return view('accounting.reports', [
            'pnl' => AccountingService::profitAndLoss($from, $to),
            'trial' => AccountingService::trialBalance($to),
            'payroll' => PayrollBatch::latest()->limit(6)->get(),
            'from' => $from, 'to' => $to,
        ]);
    }

    public function closePeriod(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('accounting.periods'), 403);
        $data = $request->validate(['month' => 'required|integer|min:1|max:12', 'year' => 'required|integer|min:2020']);
        $period = AccountingPeriod::firstOrNew(['month' => $data['month'], 'year' => $data['year']]);
        if ($period->status === 'closed') {
            throw ValidationException::withMessages(['period' => 'Period already closed.']);
        }
        $period->fill(['status' => 'closed', 'closed_by' => auth()->id(), 'closed_at' => now()])->save();
        AuditLogger::log('accounting.period_closed', 'accounting_period', $period->id);
        return back()->with('success', "Period {$data['month']}/{$data['year']} closed.");
    }

    public function storeAccount(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('accounting.journals'), 403);
        $data = $request->validate([
            'code' => 'required|string|max:20|unique:accounts,code',
            'name' => 'required|string|max:150',
            'type' => 'required|in:asset,liability,equity,income,expense',
        ]);
        Account::create($data);
        return back()->with('success', 'Account added to chart.');
    }
}
