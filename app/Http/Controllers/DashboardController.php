<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\FollowUp;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Visit;
use App\Models\VisitService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        // Notify admins about follow-ups that became due (deduped per day)
        if ($user->isAdmin() && \App\Support\Settings::bool('notify_followup_due', true)) {
            FollowUp::where('status', FollowUp::STATUS_PENDING)
                ->whereDate('due_date', '<=', today())
                ->with('patient')
                ->limit(10)
                ->get()
                ->each(fn ($f) => NotificationService::notifyFollowUpDue(
                    $f->id, $f->patient?->full_name ?? 'Patient', $f->due_date->format('d/m/Y'),
                ));
        }

        [$from, $to, $period] = $this->period($request);
        $expenseStatuses = Expense::countableStatuses();

        $data = [
            'period' => $period,
            'periodFrom' => $from,
            'periodTo' => $to,

            // Patients
            'totalPatients' => Patient::count(),
            'activePatients' => Patient::where('status', Patient::STATUS_ACTIVE)->count(),
            'newPatientsToday' => Patient::where('created_at', '>=', $today)->count(),
            'newPatientsMonth' => Patient::where('created_at', '>=', $monthStart)->count(),
            'returningPatients' => Visit::whereNotNull('patient_id')
                ->whereBetween('visit_datetime', [$from, $to->copy()->endOfDay()])
                ->select('patient_id')->groupBy('patient_id')->havingRaw('COUNT(*) > 1')->count(),

            // Visits & operations
            'visitsToday' => Visit::where('status', '!=', Visit::STATUS_VOIDED)->where('visit_datetime', '>=', $today)->count(),
            'visitsMonth' => Visit::where('status', '!=', Visit::STATUS_VOIDED)->where('visit_datetime', '>=', $monthStart)->count(),
            'visitsCompleted' => Visit::where('status', Visit::STATUS_COMPLETED)->whereBetween('visit_datetime', [$from, $to->copy()->endOfDay()])->count(),
            'visitsDraft' => Visit::where('status', Visit::STATUS_DRAFT)->whereBetween('visit_datetime', [$from, $to->copy()->endOfDay()])->count(),
            'visitsVoided' => Visit::where('status', Visit::STATUS_VOIDED)->whereBetween('visit_datetime', [$from, $to->copy()->endOfDay()])->count(),
            'servicesToday' => VisitService::whereHas('visit', fn ($v) => $v->where('status', '!=', Visit::STATUS_VOIDED)->where('visit_datetime', '>=', $today))->count(),
            'followUpsDue' => FollowUp::where('status', FollowUp::STATUS_PENDING)->whereDate('due_date', '<=', today())->count(),
            'patientsWithUpcomingFollowUps' => FollowUp::where('status', FollowUp::STATUS_PENDING)->where('due_date', '>', today())->distinct('patient_id')->count('patient_id'),
            'patientsWithoutRecentVisits' => Patient::where('status', Patient::STATUS_ACTIVE)
                ->whereDoesntHave('visits', fn ($v) => $v->where('visit_datetime', '>=', now()->subDays(90))->where('status', '!=', Visit::STATUS_VOIDED))
                ->count(),

            // Expenses
            'expensesToday' => Expense::whereIn('status', $expenseStatuses)->where('expense_date', '>=', $today)->sum('amount'),
            'expensesMonth' => Expense::whereIn('status', $expenseStatuses)->where('expense_date', '>=', $monthStart)->sum('amount'),
            'expensesPeriod' => Expense::whereIn('status', $expenseStatuses)->whereBetween('expense_date', [$from, $to])->sum('amount'),
            'pendingExpenses' => Expense::where('status', Expense::STATUS_SUBMITTED)->count(),
            'expensesByCategory' => Expense::whereIn('status', $expenseStatuses)
                ->whereBetween('expense_date', [$from, $to])
                ->selectRaw('COALESCE(category_name_snapshot, "Uncategorised") as name, SUM(amount) as total')
                ->groupBy('category_name_snapshot')->orderByDesc('total')->limit(8)->get(),

            // Recent records
            'recentPatients' => Patient::latest()->limit(5)->get(),
            'recentVisits' => Visit::with('patient')->latest('visit_datetime')->limit(5)->get(),
            'recentExpenses' => Expense::with('category', 'recorder')->latest('expense_date')->limit(5)->get(),
            'upcomingFollowUps' => FollowUp::with('patient')->where('status', FollowUp::STATUS_PENDING)
                ->orderBy('due_date')->limit(6)->get(),
        ];

        if ($user->isAdmin()) {
            $data += $this->adminMetrics($from, $to);
        }

        return view('dashboard', $data);
    }

    private function adminMetrics(Carbon $from, Carbon $to): array
    {
        $billed = Invoice::where('status', '!=', Invoice::STATUS_VOIDED)->whereBetween('issue_date', [$from, $to])->sum('total');
        $collected = Payment::where('status', Payment::STATUS_COMPLETED)->whereBetween('payment_date', [$from, $to])->sum('amount');
        $refunded = Refund::where('status', Refund::STATUS_REFUNDED)->whereBetween('created_at', [$from, $to->copy()->endOfDay()])->sum('amount');
        $outstanding = Invoice::whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIALLY_PAID])->sum('balance');

        // Last 12 months revenue vs expense trend
        $trend = collect(range(11, 0))->map(function ($i) {
            $m = now()->subMonths($i);
            return [
                'label' => $m->format('M y'),
                'revenue' => (float) Payment::where('status', Payment::STATUS_COMPLETED)
                    ->whereYear('payment_date', $m->year)->whereMonth('payment_date', $m->month)->sum('amount'),
                'expenses' => (float) Expense::whereIn('status', Expense::countableStatuses())
                    ->whereYear('expense_date', $m->year)->whereMonth('expense_date', $m->month)->sum('amount'),
                'patients' => Patient::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count(),
            ];
        });

        // Visits last 14 days
        $visitTrend = collect(range(13, 0))->map(function ($i) {
            $d = today()->subDays($i);
            return [
                'label' => $d->format('d M'),
                'visits' => Visit::where('status', '!=', Visit::STATUS_VOIDED)->whereDate('visit_datetime', $d)->count(),
            ];
        });

        $servicePopularity = VisitService::whereHas('visit', fn ($v) => $v->where('status', '!=', Visit::STATUS_VOIDED))
            ->selectRaw('service_name_snapshot as name, COUNT(*) as total')
            ->groupBy('service_name_snapshot')->orderByDesc('total')->limit(8)->get();

        $revenueByService = \App\Models\InvoiceItem::whereHas('invoice', fn ($i) => $i->where('status', '!=', Invoice::STATUS_VOIDED))
            ->selectRaw('service_name as name, SUM(line_total) as total')
            ->groupBy('service_name')->orderByDesc('total')->limit(8)->get();

        return [
            'billed' => $billed,
            'collected' => $collected,
            'refunded' => $refunded,
            'outstanding' => $outstanding,
            'trend' => $trend,
            'visitTrend' => $visitTrend,
            'servicePopularity' => $servicePopularity,
            'revenueByService' => $revenueByService,
            'pendingInvoices' => Invoice::whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIALLY_PAID])->count(),
        ];
    }

    private function period(Request $request): array
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
