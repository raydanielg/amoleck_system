<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\VisitService;
use App\Services\AuditLogger;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public const TYPES = [
        'patient-register' => 'Patient Register',
        'registrations' => 'Patient Registrations',
        'visits' => 'Patient Visits',
        'service-usage' => 'Service Usage',
        'expenses' => 'Expenses Detail',
        'expense-summary' => 'Expenses by Category',
        'revenue' => 'Revenue (Collected Payments)',
        'outstanding-invoices' => 'Outstanding Invoices',
        'refunds' => 'Refunds',
        'discounts' => 'Discounts Granted',
        'revenue-vs-expenses' => 'Revenue vs Expenses',
        'expense-approvals' => 'Expense Approval History',
        'staff-activity' => 'Staff Activity Summary',
    ];

    /** Reports restricted to administrators (financial + sensitive). */
    private const ADMIN_TYPES = [
        'revenue', 'outstanding-invoices', 'refunds', 'discounts',
        'revenue-vs-expenses', 'expense-approvals', 'staff-activity',
    ];

    public const GROUPS = [
        'Patient Reports' => ['patient-register', 'registrations', 'visits'],
        'Service Reports' => ['service-usage'],
        'Financial Reports' => ['revenue', 'outstanding-invoices', 'refunds', 'discounts', 'revenue-vs-expenses', 'expenses', 'expense-summary', 'expense-approvals'],
        'Staff Reports' => ['staff-activity'],
    ];

    public function index(): View
    {
        $groups = self::GROUPS;
        if (! request()->user()->isAdmin()) {
            $groups['Financial Reports'] = ['expenses', 'expense-summary'];
            unset($groups['Staff Reports']);
        }

        return view('reports.index', ['types' => self::TYPES, 'groups' => $groups]);
    }

    public function show(Request $request, string $type): View
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        abort_if(in_array($type, self::ADMIN_TYPES) && ! $request->user()->isAdmin(), 403);

        [$from, $to] = $this->dateRange($request);
        $data = $this->build($type, $from, $to, $request);

        return view('reports.show', [
            'type' => $type,
            'title' => self::TYPES[$type],
            'from' => $from,
            'to' => $to,
            ...$data,
        ]);
    }

    public function export(Request $request, string $type, string $format)
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        abort_unless(in_array($format, ['csv', 'pdf']), 404);
        abort_if(in_array($type, self::ADMIN_TYPES) && ! $request->user()->isAdmin(), 403);

        [$from, $to] = $this->dateRange($request);
        $data = $this->build($type, $from, $to, $request);

        AuditLogger::log('report.exported', 'report', null, null, [
            'type' => $type, 'format' => $format, 'from' => $from, 'to' => $to,
        ]);

        $filename = "{$type}_{$from}_{$to}";

        if ($format === 'csv') {
            return $this->exportCsv($filename, $data['columns'], $data['rows']);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf', [
            'title' => self::TYPES[$type],
            'from' => $from,
            'to' => $to,
            'columns' => $data['columns'],
            'rows' => $data['rows'],
            'totals' => $data['totals'] ?? [],
            'clinic' => [
                'name' => Settings::get('clinic_name', config('app.name')),
                'address' => Settings::get('clinic_address'),
                'phone' => Settings::get('clinic_phone'),
                'email' => Settings::get('clinic_email'),
                'footer' => Settings::get('report_footer'),
                'signature' => Settings::get('report_signature'),
            ],
            'generatedBy' => $request->user()->name,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download("{$filename}.pdf");
    }

    private function dateRange(Request $request): array
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());

        return [$from, $to];
    }

    private function build(string $type, string $from, string $to, Request $request): array
    {
        return match ($type) {
            'patient-register' => [
                'columns' => ['Patient No', 'Full Name', 'Sex', 'Phone', 'Registered', 'Status'],
                'rows' => Patient::orderBy('created_at')->get()->map(fn ($p) => [
                    $p->patient_number, $p->full_name, $p->sex, $p->phone,
                    $p->created_at->format('Y-m-d'), $p->status,
                ])->all(),
            ],
            'registrations' => [
                'columns' => ['Patient No', 'Full Name', 'Sex', 'Phone', 'Registered', 'Registered By'],
                'rows' => Patient::with('creator')
                    ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
                    ->orderBy('created_at')->get()->map(fn ($p) => [
                        $p->patient_number, $p->full_name, $p->sex, $p->phone,
                        $p->created_at->format('Y-m-d'), $p->creator?->name,
                    ])->all(),
            ],
            'visits' => $this->visitsReport($from, $to, $request),
            'service-usage' => [
                'columns' => ['Service', 'Code', 'Visits Count'],
                'rows' => VisitService::join('visits', 'visit_services.visit_id', '=', 'visits.id')
                    ->where('visits.status', '!=', Visit::STATUS_VOIDED)
                    ->whereBetween('visits.visit_datetime', [$from.' 00:00:00', $to.' 23:59:59'])
                    ->selectRaw('service_name_snapshot, service_code_snapshot, COUNT(*) as total')
                    ->groupBy('service_name_snapshot', 'service_code_snapshot')
                    ->orderByDesc('total')
                    ->get()->map(fn ($r) => [$r->service_name_snapshot, $r->service_code_snapshot, $r->total])->all(),
            ],
            'expenses' => $this->expensesReport($from, $to, $request),
            'expense-summary' => $this->expenseSummary($from, $to),
            'revenue' => $this->revenueReport($from, $to, $request),
            'outstanding-invoices' => $this->outstandingReport(),
            'refunds' => $this->refundsReport($from, $to),
            'discounts' => $this->discountsReport($from, $to),
            'revenue-vs-expenses' => $this->revenueVsExpenses($from, $to),
            'expense-approvals' => $this->approvalsReport($from, $to),
            'staff-activity' => $this->staffActivityReport($from, $to),
        };
    }

    private function revenueReport(string $from, string $to, Request $request): array
    {
        $currency = Settings::get('currency', 'TZS');
        $query = \App\Models\Payment::with('invoice', 'patient', 'receiver')
            ->where('status', \App\Models\Payment::STATUS_COMPLETED)
            ->whereBetween('payment_date', [$from, $to])
            ->orderBy('payment_date');

        if ($method = $request->query('method')) {
            $query->where('payment_method', $method);
        }

        $payments = $query->get();

        return [
            'columns' => ['Reference', 'Invoice', 'Patient', 'Date', 'Method', "Amount ({$currency})", 'Received By'],
            'rows' => $payments->map(fn ($p) => [
                $p->payment_reference, $p->invoice?->invoice_reference, $p->patient?->full_name,
                $p->payment_date->format('Y-m-d'), $p->payment_method,
                number_format((float) $p->amount, 2), $p->receiver?->name,
            ])->all(),
            'totals' => [['label' => 'Total collected', 'value' => $currency.' '.number_format((float) $payments->sum('amount'), 2)]],
        ];
    }

    private function outstandingReport(): array
    {
        $currency = Settings::get('currency', 'TZS');
        $invoices = \App\Models\Invoice::with('patient')
            ->whereIn('status', [\App\Models\Invoice::STATUS_ISSUED, \App\Models\Invoice::STATUS_PARTIALLY_PAID])
            ->orderBy('issue_date')->get();

        return [
            'columns' => ['Invoice', 'Patient', 'Issue Date', "Total ({$currency})", "Paid ({$currency})", "Balance ({$currency})"],
            'rows' => $invoices->map(fn ($i) => [
                $i->invoice_reference, $i->patient?->full_name, $i->issue_date->format('Y-m-d'),
                number_format((float) $i->total, 2), number_format((float) $i->amount_paid, 2),
                number_format((float) $i->balance, 2),
            ])->all(),
            'totals' => [['label' => 'Total outstanding', 'value' => $currency.' '.number_format((float) $invoices->sum('balance'), 2)]],
        ];
    }

    private function refundsReport(string $from, string $to): array
    {
        $currency = Settings::get('currency', 'TZS');
        $refunds = \App\Models\Refund::with('payment.patient', 'refunder')
            ->where('status', \App\Models\Refund::STATUS_REFUNDED)
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->latest()->get();

        return [
            'columns' => ['Refund', 'Payment', 'Patient', "Amount ({$currency})", 'Reason', 'By', 'Date'],
            'rows' => $refunds->map(fn ($r) => [
                $r->refund_reference, $r->payment?->payment_reference, $r->payment?->patient?->full_name,
                number_format((float) $r->amount, 2), $r->reason, $r->refunder?->name,
                $r->created_at->format('Y-m-d'),
            ])->all(),
            'totals' => [['label' => 'Total refunded', 'value' => $currency.' '.number_format((float) $refunds->sum('amount'), 2)]],
        ];
    }

    private function discountsReport(string $from, string $to): array
    {
        $currency = Settings::get('currency', 'TZS');
        $invoices = \App\Models\Invoice::with('patient')
            ->where('status', '!=', \App\Models\Invoice::STATUS_VOIDED)
            ->where('discount_amount', '>', 0)
            ->whereBetween('issue_date', [$from, $to])
            ->orderBy('issue_date')->get();

        return [
            'columns' => ['Invoice', 'Patient', 'Date', "Subtotal ({$currency})", "Discount ({$currency})", 'Reason'],
            'rows' => $invoices->map(fn ($i) => [
                $i->invoice_reference, $i->patient?->full_name, $i->issue_date->format('Y-m-d'),
                number_format((float) $i->subtotal, 2), number_format((float) $i->discount_amount, 2),
                $i->discount_reason,
            ])->all(),
            'totals' => [['label' => 'Total discounts', 'value' => $currency.' '.number_format((float) $invoices->sum('discount_amount'), 2)]],
        ];
    }

    private function revenueVsExpenses(string $from, string $to): array
    {
        $currency = Settings::get('currency', 'TZS');
        $collected = (float) \App\Models\Payment::where('status', \App\Models\Payment::STATUS_COMPLETED)->whereBetween('payment_date', [$from, $to])->sum('amount');
        $refunded = (float) \App\Models\Refund::where('status', \App\Models\Refund::STATUS_REFUNDED)->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])->sum('amount');
        $billed = (float) \App\Models\Invoice::where('status', '!=', \App\Models\Invoice::STATUS_VOIDED)->whereBetween('issue_date', [$from, $to])->sum('total');
        $expenses = (float) Expense::whereIn('status', Expense::countableStatuses())->whereBetween('expense_date', [$from, $to])->sum('amount');
        $net = $collected - $refunded;

        return [
            'columns' => ['Metric', "Amount ({$currency})"],
            'rows' => [
                ['Billed revenue (invoices issued)', number_format($billed, 2)],
                ['Collected revenue (payments)', number_format($collected, 2)],
                ['Refunds', number_format($refunded, 2)],
                ['Net collected', number_format($net, 2)],
                ['Approved expenses', number_format($expenses, 2)],
                ['Operating surplus', number_format($net - $expenses, 2)],
            ],
            'totals' => [['label' => 'Surplus', 'value' => $currency.' '.number_format($net - $expenses, 2)]],
        ];
    }

    private function approvalsReport(string $from, string $to): array
    {
        $approvals = \App\Models\ExpenseApproval::with('expense', 'reviewer')
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->latest()->get();

        return [
            'columns' => ['Expense', 'Decision', 'Reviewer', 'Previous', 'New Status', 'Reason', 'Date'],
            'rows' => $approvals->map(fn ($a) => [
                $a->expense?->expense_reference, $a->decision, $a->reviewer?->name,
                $a->previous_status, $a->new_status, $a->reason,
                $a->created_at->format('Y-m-d H:i'),
            ])->all(),
            'totals' => [['label' => 'Decisions', 'value' => $approvals->count()]],
        ];
    }

    private function staffActivityReport(string $from, string $to): array
    {
        $rows = \App\Models\User::where('status', \App\Models\User::STATUS_ACTIVE)
            ->get()
            ->map(function ($user) use ($from, $to) {
                return [
                    $user->name,
                    $user->role === \App\Models\User::ROLE_ADMIN ? 'Admin' : 'Staff',
                    Patient::where('created_by', $user->id)->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])->count(),
                    Visit::where('recorded_by', $user->id)->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])->count(),
                    Expense::where('recorded_by', $user->id)->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])->count(),
                    $user->last_login_at?->format('Y-m-d H:i') ?? 'Never',
                ];
            })->all();

        return [
            'columns' => ['Staff Member', 'Role', 'Patients Registered', 'Visits Recorded', 'Expenses Submitted', 'Last Login'],
            'rows' => $rows,
        ];
    }

    private function visitsReport(string $from, string $to, Request $request): array
    {
        $query = Visit::with('patient', 'recorder')
            ->where('status', '!=', Visit::STATUS_VOIDED)
            ->whereBetween('visit_datetime', [$from.' 00:00:00', $to.' 23:59:59'])
            ->orderBy('visit_datetime');

        if ($staffId = $request->query('staff')) {
            $query->where('recorded_by', $staffId);
        }

        $visits = $query->get();

        return [
            'columns' => ['Reference', 'Patient', 'Date', 'Services', 'Recorded By', 'Status'],
            'rows' => $visits->map(fn ($v) => [
                $v->visit_reference,
                $v->patient?->full_name.' ('.$v->patient?->patient_number.')',
                $v->visit_datetime->format('Y-m-d H:i'),
                $v->services->pluck('service_name_snapshot')->implode(', '),
                $v->recorder?->name,
                $v->status,
            ])->all(),
            'totals' => [['label' => 'Total visits', 'value' => $visits->count()]],
        ];
    }

    private function expensesReport(string $from, string $to, Request $request): array
    {
        $query = Expense::with('category', 'recorder')
            ->whereBetween('expense_date', [$from, $to])
            ->orderBy('expense_date');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($categoryId = $request->query('category')) {
            $query->where('expense_category_id', $categoryId);
        }

        $expenses = $query->get();
        $total = $expenses->whereIn('status', [Expense::STATUS_SUBMITTED, Expense::STATUS_APPROVED])->sum('amount');
        $currency = Settings::get('currency', 'TZS');

        return [
            'columns' => ['Reference', 'Date', 'Title', 'Category', 'Payee', 'Method', "Amount ({$currency})", 'Status', 'Recorded By'],
            'rows' => $expenses->map(fn ($e) => [
                $e->expense_reference,
                $e->expense_date->format('Y-m-d'),
                $e->title,
                $e->category_name_snapshot,
                $e->payee,
                $e->payment_method,
                number_format((float) $e->amount, 2),
                $e->status,
                $e->recorder?->name,
            ])->all(),
            'totals' => [['label' => 'Total', 'value' => $currency.' '.number_format((float) $total, 2)]],
        ];
    }

    private function expenseSummary(string $from, string $to): array
    {
        $currency = Settings::get('currency', 'TZS');
        $rows = Expense::whereIn('status', [Expense::STATUS_SUBMITTED, Expense::STATUS_APPROVED])
            ->whereBetween('expense_date', [$from, $to])
            ->selectRaw('COALESCE(category_name_snapshot, "Uncategorised") as name, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('category_name_snapshot')
            ->orderByDesc('total')
            ->get();

        return [
            'columns' => ['Category', 'Expenses', "Total ({$currency})"],
            'rows' => $rows->map(fn ($r) => [$r->name, $r->count, number_format((float) $r->total, 2)])->all(),
            'totals' => [['label' => 'Grand total', 'value' => $currency.' '.number_format((float) $rows->sum('total'), 2)]],
        ];
    }

    private function exportCsv(string $filename, array $columns, array $rows): StreamedResponse
    {
        return Response::streamDownload(function () use ($columns, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, "{$filename}.csv", ['Content-Type' => 'text/csv']);
    }
}
