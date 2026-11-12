<?php

namespace App\Services;

use App\Models\ManagementInsight;
use App\Models\ManagementKpi;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /** Period bounds: [from, to, prevFrom, prevTo] for comparison. */
    public static function periods(string $preset, ?string $from = null, ?string $to = null): array
    {
        $now = now();
        return match ($preset) {
            'today' => [$now->toDateString(), $now->toDateString(), $now->copy()->subDay()->toDateString(), $now->copy()->subDay()->toDateString()],
            'yesterday' => [$now->copy()->subDay()->toDateString(), $now->copy()->subDay()->toDateString(), $now->copy()->subDays(2)->toDateString(), $now->copy()->subDays(2)->toDateString()],
            'last7' => [$now->copy()->subDays(6)->toDateString(), $now->toDateString(), $now->copy()->subDays(13)->toDateString(), $now->copy()->subDays(7)->toDateString()],
            'last30' => [$now->copy()->subDays(29)->toDateString(), $now->toDateString(), $now->copy()->subDays(59)->toDateString(), $now->copy()->subDays(30)->toDateString()],
            'last_month' => [$now->copy()->subMonth()->startOfMonth()->toDateString(), $now->copy()->subMonth()->endOfMonth()->toDateString(), $now->copy()->subMonths(2)->startOfMonth()->toDateString(), $now->copy()->subMonths(2)->endOfMonth()->toDateString()],
            'year' => [$now->startOfYear()->toDateString(), $now->toDateString(), $now->copy()->subYear()->startOfYear()->toDateString(), $now->copy()->subYear()->endOfYear()->toDateString()],
            'custom' => [$from ?? $now->toDateString(), $to ?? $now->toDateString(), null, null],
            default => [$now->startOfMonth()->toDateString(), $now->toDateString(), $now->copy()->subMonth()->startOfMonth()->toDateString(), $now->copy()->subMonth()->endOfMonth()->toDateString()],
        };
    }

    public static function change(float $current, float $previous): ?float
    {
        return $previous != 0.0 ? round(($current - $previous) / abs($previous) * 100, 1) : null;
    }

    /** Core KPIs for a period. */
    public static function kpis(string $from, string $to, ?int $branchId = null): array
    {
        $branch = fn ($q) => $branchId ? $q->where('branch_id', $branchId) : $q;
        $payQ = DB::table('payments')->where('verification_status', 'verified')
            ->whereBetween(DB::raw('DATE(verified_at)'), [$from, $to]);
        $revenue = (float) $branch($payQ->join('invoices', 'invoices.id', '=', 'payments.invoice_id'))->sum('payments.amount')
            + (float) DB::table('sale_payments')->join('sales', 'sales.id', '=', 'sale_payments.sale_id')->whereBetween(DB::raw('DATE(sale_payments.created_at)'), [$from, $to])
                ->when($branchId, fn ($q) => $q->where('sales.branch_id', $branchId))->sum('sale_payments.amount');

        return [
            'revenue' => $revenue,
            'expenses' => (float) $branch(DB::table('expenses')->where('status', 'paid')->whereBetween('expense_date', [$from, $to]))->sum('amount'),
            'patients' => (int) $branch(DB::table('patients')->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]))->count(),
            'appointments' => (int) $branch(DB::table('appointments')->whereDate('scheduled_at', '>=', $from)->whereDate('scheduled_at', '<=', $to))->count(),
            'appointments_completed' => (int) $branch(DB::table('appointments')->where('status', 'completed')->whereDate('scheduled_at', '>=', $from)->whereDate('scheduled_at', '<=', $to))->count(),
            'appointments_cancelled' => (int) $branch(DB::table('appointments')->where('status', 'cancelled')->whereDate('scheduled_at', '>=', $from)->whereDate('scheduled_at', '<=', $to))->count(),
            'visits' => (int) $branch(DB::table('visits')->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]))->count(),
            'outstanding' => (float) $branch(DB::table('invoices')->where('balance', '>', 0)->whereIn('status', ['issued', 'partially_paid']))->sum('balance'),
        ];
    }

    /** Monthly revenue vs expenses trend (12 months). */
    public static function monthlyTrend(): array
    {
        $rows = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $rows[] = [
                'label' => $m->format('M'),
                'revenue' => (float) AccountingService::balance(AccountingService::SERVICE_REVENUE, $m->endOfMonth()->toDateString())
                    + (float) AccountingService::balance(AccountingService::PRODUCT_REVENUE, $m->endOfMonth()->toDateString())
                    - ((float) AccountingService::balance(AccountingService::SERVICE_REVENUE, $m->copy()->subMonth()->endOfMonth()->toDateString())
                    + (float) AccountingService::balance(AccountingService::PRODUCT_REVENUE, $m->copy()->subMonth()->endOfMonth()->toDateString())),
            ];
        }
        return $rows;
    }

    public static function expenseByCategory(string $from, string $to): array
    {
        return DB::table('expenses')->where('status', 'paid')->whereBetween('expense_date', [$from, $to])
            ->select('category_name_snapshot as label', DB::raw('SUM(amount) total'))->groupBy('category_name_snapshot')
            ->orderByDesc('total')->limit(8)->get()->map(fn ($r) => ['label' => $r->label ?? 'Other', 'total' => (float) $r->total])->all();
    }

    public static function servicePerformance(string $from, string $to): array
    {
        return DB::table('invoice_items')->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoice_items.item_type', 'service')->whereIn('invoices.status', ['issued', 'partially_paid', 'paid'])
            ->whereBetween('invoices.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->select('invoice_items.service_name as label', DB::raw('COUNT(*) sessions'), DB::raw('SUM(invoice_items.line_total) revenue'))
            ->groupBy('invoice_items.service_name')->orderByDesc('revenue')->limit(10)->get()
            ->map(fn ($r) => ['label' => $r->label, 'sessions' => (int) $r->sessions, 'revenue' => (float) $r->revenue])->all();
    }

    public static function branchComparison(string $from, string $to): array
    {
        return DB::table('branches')->where('status', 'active')->get()->map(fn ($b) => [
            'name' => $b->name,
            'patients' => DB::table('patients')->where('branch_id', $b->id)->count(),
            'revenue' => (float) DB::table('invoices')->where('branch_id', $b->id)->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])->sum('total'),
            'expenses' => (float) DB::table('expenses')->where('branch_id', $b->id)->where('status', 'paid')->whereBetween('expense_date', [$from, $to])->sum('amount'),
            'employees' => DB::table('employees')->where('branch_id', $b->id)->where('status', 'active')->count(),
        ])->all();
    }

    public static function patientGrowth(): array
    {
        $rows = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $rows[] = ['label' => $m->format('M'), 'new' => DB::table('patients')->whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->count()];
        }
        return $rows;
    }

    public static function inventorySummary(): array
    {
        return [
            'value' => (float) DB::table('products')->selectRaw('SUM(purchase_price * stock_quantity) v')->value('v'),
            'low_stock' => DB::table('products')->whereColumn('stock_quantity', '<=', 'min_stock_level')->where('stock_quantity', '>', 0)->count(),
            'out_of_stock' => DB::table('products')->where('stock_quantity', '<=', 0)->count(),
            'batches_expiring' => InventoryBatchService::expirySummary()['expiring'],
        ];
    }

    public static function hrSummary(): array
    {
        return [
            'employees' => DB::table('employees')->where('status', 'active')->count(),
            'on_leave' => DB::table('leave_requests')->where('status', 'approved')->whereDate('start_date', '<=', now())->whereDate('end_date', '>=', now())->count(),
            'present_today' => DB::table('attendance_records')->whereDate('date', today())->whereIn('status', ['present', 'late'])->count(),
            'late_today' => DB::table('attendance_records')->whereDate('date', today())->where('status', 'late')->count(),
        ];
    }

    /** Generate/refresh insights comparing current vs previous period. */
    public static function generateInsights(string $from, string $to, ?string $prevFrom, ?string $prevTo): int
    {
        if (! $prevFrom) return 0;
        $cur = self::kpis($from, $to);
        $prev = self::kpis($prevFrom, $prevTo);
        $made = 0;

        $rules = [
            ['key' => 'revenue', 'label' => 'Revenue', 'cat' => 'financial', 'bad_when' => 'down'],
            ['key' => 'expenses', 'label' => 'Expenses', 'cat' => 'financial', 'bad_when' => 'up'],
            ['key' => 'patients', 'label' => 'New patient registrations', 'cat' => 'patients', 'bad_when' => 'down'],
            ['key' => 'appointments_completed', 'label' => 'Completed appointments', 'cat' => 'operational', 'bad_when' => 'down'],
        ];
        foreach ($rules as $r) {
            $chg = self::change((float) $cur[$r['key']], (float) $prev[$r['key']]);
            if ($chg === null || abs($chg) < 10) continue;
            $bad = ($r['bad_when'] === 'down' && $chg < 0) || ($r['bad_when'] === 'up' && $chg > 0);
            $key = $r['key'].'.'.date('ymd');
            ManagementInsight::updateOrCreate(
                ['key' => $key],
                ['title' => $r['label'].' '.($chg > 0 ? 'increased' : 'decreased').' '.abs($chg).'%',
                 'category' => $r['cat'], 'severity' => $bad ? 'warning' : 'info',
                 'detail' => $r['label'].' is '.($chg > 0 ? 'up' : 'down').' '.abs($chg).'% vs previous period ('.number_format($prev[$r['key']]).' → '.number_format($cur[$r['key']]).').',
                 'data' => ['current' => $cur[$r['key']], 'previous' => $prev[$r['key']], 'change' => $chg]]
            );
            $made++;
        }
        return $made;
    }

    /** Evaluate active KPI targets against current period. */
    public static function kpiStatus(string $from, string $to): array
    {
        $kpis = self::kpis($from, $to);
        $out = [];
        foreach (ManagementKpi::where('is_active', true)->get() as $k) {
            $actual = $kpis[$k->key] ?? null;
            $out[] = ['kpi' => $k, 'actual' => $actual, 'pct' => $actual !== null && (float) $k->target > 0 ? round($actual / (float) $k->target * 100, 1) : null];
        }
        return $out;
    }
}
