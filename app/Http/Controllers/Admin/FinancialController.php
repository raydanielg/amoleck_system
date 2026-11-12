<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Refund;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinancialController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to, $period] = $this->range($request);

        $collected = Payment::where('status', Payment::STATUS_COMPLETED)
            ->whereBetween('payment_date', [$from, $to])->sum('amount');
        $refunded = Refund::where('status', Refund::STATUS_REFUNDED)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->sum('amount');
        $billed = Invoice::where('status', '!=', Invoice::STATUS_VOIDED)
            ->whereBetween('issue_date', [$from, $to])->sum('total');
        $discounts = Invoice::where('status', '!=', Invoice::STATUS_VOIDED)
            ->whereBetween('issue_date', [$from, $to])->sum('discount_amount');
        $approvedExpenses = Expense::whereIn('status', Expense::countableStatuses())
            ->whereBetween('expense_date', [$from, $to])->sum('amount');
        $pendingApprovals = Expense::where('status', Expense::STATUS_SUBMITTED)->count();
        $outstanding = Invoice::whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIALLY_PAID])
            ->sum('balance');

        $netCollected = bcsub((string) $collected, (string) $refunded, 2);

        // Product sales (medical supplies) — kept separate from service revenue
        $salesCollected = \App\Models\SalePayment::where('status', \App\Models\SalePayment::STATUS_COMPLETED)
            ->whereBetween('payment_date', [$from, $to])->sum('amount');
        $salesRefunded = \App\Models\SaleRefund::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->sum('amount');
        $salesBilled = \App\Models\Sale::where('status', '!=', \App\Models\Sale::STATUS_CANCELLED)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->sum('total');
        $salesOutstanding = \App\Models\Sale::whereIn('status', [\App\Models\Sale::STATUS_PENDING, \App\Models\Sale::STATUS_PARTIAL])->sum('balance');
        $cogs = \App\Models\SaleItem::whereHas('sale', fn ($q) => $q->where('status', '!=', \App\Models\Sale::STATUS_CANCELLED)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]))
            ->selectRaw('COALESCE(SUM(unit_cost * quantity),0) as c')->value('c');
        $grossProductProfit = bcsub((string) $salesBilled, (string) $cogs, 2);

        $netSales = bcsub((string) $salesCollected, (string) $salesRefunded, 2);
        $totalCollected = bcadd($netCollected, $netSales, 2);
        $surplus = bcsub($totalCollected, (string) $approvedExpenses, 2);

        // Monthly trend (last 12 months)
        $trend = collect(range(11, 0))->map(function ($i) {
            $m = now()->subMonths($i);
            return [
                'label' => $m->format('M Y'),
                'revenue' => (float) Payment::where('status', Payment::STATUS_COMPLETED)
                    ->whereYear('payment_date', $m->year)->whereMonth('payment_date', $m->month)->sum('amount'),
                'expenses' => (float) Expense::whereIn('status', Expense::countableStatuses())
                    ->whereYear('expense_date', $m->year)->whereMonth('expense_date', $m->month)->sum('amount'),
            ];
        });

        $revenueByMethod = Payment::where('status', Payment::STATUS_COMPLETED)
            ->whereBetween('payment_date', [$from, $to])
            ->selectRaw('payment_method as name, SUM(amount) as total')
            ->groupBy('payment_method')->orderByDesc('total')->get();

        $recentPayments = Payment::with('invoice', 'patient', 'receiver')
            ->where('status', Payment::STATUS_COMPLETED)->latest('payment_date')->limit(8)->get();

        $recentInvoices = Invoice::with('patient')->where('status', '!=', Invoice::STATUS_VOIDED)
            ->latest('issue_date')->limit(8)->get();

        return view('admin.financials.index', [
            'period' => $period, 'from' => $from, 'to' => $to,
            'billed' => $billed, 'collected' => $collected, 'refunded' => $refunded,
            'discounts' => $discounts, 'netCollected' => $netCollected,
            'approvedExpenses' => $approvedExpenses, 'surplus' => $surplus,
            'outstanding' => $outstanding, 'pendingApprovals' => $pendingApprovals,
            'salesCollected' => $salesCollected, 'salesRefunded' => $salesRefunded,
            'salesBilled' => $salesBilled, 'salesOutstanding' => $salesOutstanding,
            'cogs' => $cogs, 'grossProductProfit' => $grossProductProfit,
            'netSales' => $netSales, 'totalCollected' => $totalCollected,
            'trend' => $trend, 'revenueByMethod' => $revenueByMethod,
            'recentPayments' => $recentPayments, 'recentInvoices' => $recentInvoices,
        ]);
    }

    private function range(Request $request): array
    {
        $period = $request->query('period', 'month');

        [$from, $to] = match ($period) {
            'today' => [today(), today()],
            'yesterday' => [today()->subDay(), today()->subDay()],
            '7d' => [today()->subDays(6), today()],
            '30d' => [today()->subDays(29), today()],
            'month' => [today()->startOfMonth(), today()],
            'last_month' => [today()->subMonth()->startOfMonth(), today()->subMonth()->endOfMonth()],
            'quarter' => [today()->startOfQuarter(), today()],
            'year' => [today()->startOfYear(), today()],
            'custom' => [
                Carbon::parse($request->query('from', today()->subDays(29))),
                Carbon::parse($request->query('to', today())),
            ],
            default => [today()->startOfMonth(), today()],
        };

        return [$from, $to, $period];
    }
}
