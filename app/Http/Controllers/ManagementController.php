<?php

namespace App\Http\Controllers;

use App\Models\ManagementInsight;
use App\Models\ManagementKpi;
use App\Services\AnalyticsService;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ManagementController extends Controller
{
    private function periods(Request $request): array
    {
        return AnalyticsService::periods($request->get('period', 'month'), $request->get('from'), $request->get('to'));
    }

    public function dashboard(Request $request)
    {
        [$from, $to, $pf, $pt] = $this->periods($request);
        $branchId = $request->integer('branch_id') ?: null;
        $cur = AnalyticsService::kpis($from, $to, $branchId);
        $prev = $pf ? AnalyticsService::kpis($pf, $pt, $branchId) : [];

        $kpis = [];
        foreach (['revenue' => 'Revenue', 'expenses' => 'Expenses', 'patients' => 'New Patients', 'appointments' => 'Appointments', 'appointments_completed' => 'Completed', 'appointments_cancelled' => 'Cancelled', 'visits' => 'Visits', 'outstanding' => 'Outstanding'] as $key => $label) {
            $kpis[$key] = ['label' => $label, 'value' => $cur[$key], 'change' => $pf ? AnalyticsService::change((float) $cur[$key], (float) ($prev[$key] ?? 0)) : null, 'inverse' => in_array($key, ['expenses', 'appointments_cancelled', 'outstanding'])];
        }

        return view('management.dashboard', [
            'kpis' => $kpis,
            'pnl' => \App\Services\AccountingService::profitAndLoss($from, $to),
            'trend' => AnalyticsService::monthlyTrend(),
            'expenseCats' => AnalyticsService::expenseByCategory($from, $to),
            'services' => AnalyticsService::servicePerformance($from, $to),
            'branches' => auth()->user()->hasPermission('exec.branches') ? AnalyticsService::branchComparison($from, $to) : [],
            'patientGrowth' => AnalyticsService::patientGrowth(),
            'inventory' => AnalyticsService::inventorySummary(),
            'hr' => auth()->user()->hasPermission('exec.hr') ? AnalyticsService::hrSummary() : null,
            'insights' => auth()->user()->hasPermission('exec.insights') ? ManagementInsight::where('status', 'new')->latest()->limit(6)->get() : collect(),
            'kpiStatus' => AnalyticsService::kpiStatus($from, $to),
            'from' => $from, 'to' => $to, 'period' => $request->get('period', 'month'),
            'branchList' => \App\Models\Branch::where('status', 'active')->get(),
            'branchId' => $branchId,
        ]);
    }

    public function refreshInsights(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('exec.insights'), 403);
        [$from, $to, $pf, $pt] = $this->periods($request);
        $n = AnalyticsService::generateInsights($from, $to, $pf, $pt);
        return back()->with('success', "{$n} insights updated.");
    }

    public function acknowledgeInsight(ManagementInsight $insight)
    {
        abort_unless(auth()->user()->hasPermission('exec.insights'), 403);
        $insight->update(['status' => 'acknowledged', 'acknowledged_by' => auth()->id(), 'acknowledged_at' => now()]);
        return back()->with('success', 'Insight acknowledged.');
    }

    public function storeKpi(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('exec.dashboard'), 403);
        $data = $request->validate([
            'key' => 'required|string|max:60|in:revenue,expenses,patients,appointments,appointments_completed,visits',
            'name' => 'required|string|max:100', 'target' => 'required|numeric|min:0',
            'unit' => 'required|in:value,percent,currency,count', 'branch_id' => 'nullable|exists:branches,id',
        ]);
        ManagementKpi::updateOrCreate(['key' => $data['key']], $data);
        return back()->with('success', 'KPI target saved.');
    }

    /** Monthly management report PDF. */
    public function reportPdf(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('exec.reports'), 403);
        $from = now()->startOfMonth()->toDateString();
        $to = now()->toDateString();
        $pdf = Pdf::loadView('pdf.management-report', [
            'kpis' => AnalyticsService::kpis($from, $to),
            'pnl' => \App\Services\AccountingService::profitAndLoss($from, $to),
            'services' => AnalyticsService::servicePerformance($from, $to),
            'branches' => AnalyticsService::branchComparison($from, $to),
            'hr' => AnalyticsService::hrSummary(),
            'inventory' => AnalyticsService::inventorySummary(),
            'insights' => ManagementInsight::where('status', 'new')->latest()->limit(10)->get(),
            'clinic' => \App\Support\Settings::get('clinic_name'),
            'period' => now()->format('F Y'),
        ]);
        AuditLogger::log('mgmt.report_generated');
        return $pdf->download('management-report-'.now()->format('Y-m').'.pdf');
    }
}
