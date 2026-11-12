@php
    $cur = \App\Support\Settings::get('currency', 'TZS');
    $money = fn ($v) => $cur.' '.number_format((float) $v);
    $periods = [
        'today' => 'Today', 'yesterday' => 'Yesterday', '7d' => '7 Days', '30d' => '30 Days',
        'month' => 'This Month', 'last_month' => 'Last Month', 'quarter' => 'This Quarter', 'year' => 'This Year',
    ];
@endphp
<x-app-layout>
    <x-slot name="header">{{ __('Financial Overview') }}</x-slot>
    <x-slot name="headerActions">
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle">{{ $periods[$period] ?? 'This Month' }}</button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach ($periods as $key => $label)
                    <li><a class="dropdown-item {{ $period === $key ? 'active' : '' }}" href="{{ route('admin.financials.index', ['period' => $key]) }}">{{ $label }}</a></li>
                @endforeach
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="GET" class="px-3 py-1" style="min-width: 240px;">
                        <input type="hidden" name="period" value="custom">
                        <div class="d-flex gap-1 mb-1">
                            <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                            <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                        </div>
                        <button class="btn btn-primary btn-sm w-100">Apply Range</button>
                    </form>
                </li>
            </ul>
        </div>
    </x-slot>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Gross Billed</div><div class="stat-value">{{ $money($billed) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Collected</div><div class="stat-value text-success">{{ $money($collected) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Outstanding (All Time)</div><div class="stat-value text-warning">{{ $money($outstanding) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Discounts</div><div class="stat-value">{{ $money($discounts) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Refunds</div><div class="stat-value text-danger">{{ $money($refunded) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Net Collected</div><div class="stat-value">{{ $money($netCollected) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Approved Expenses</div><div class="stat-value text-danger">{{ $money($approvedExpenses) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Operating Surplus</div><div class="stat-value {{ (float) $surplus >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($surplus) }}</div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold"><i class="hgi-stroke hgi-stethoscope-02 me-1"></i>Physiotherapy Services</div>
                <div class="card-body row g-2 small">
                    <div class="col-6"><div class="text-muted">Billed</div><div class="fw-semibold">{{ $money($billed) }}</div></div>
                    <div class="col-6"><div class="text-muted">Collected</div><div class="fw-semibold text-success">{{ $money($collected) }}</div></div>
                    <div class="col-6"><div class="text-muted">Outstanding</div><div class="fw-semibold text-warning">{{ $money($outstanding) }}</div></div>
                    <div class="col-6"><div class="text-muted">Refunded</div><div class="fw-semibold text-danger">{{ $money($refunded) }}</div></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold"><i class="hgi-stroke hgi-package me-1"></i>Medical Supplies Sales</div>
                <div class="card-body row g-2 small">
                    <div class="col-6"><div class="text-muted">Sales (billed)</div><div class="fw-semibold">{{ $money($salesBilled) }}</div></div>
                    <div class="col-6"><div class="text-muted">Collected</div><div class="fw-semibold text-success">{{ $money($salesCollected) }}</div></div>
                    <div class="col-6"><div class="text-muted">Cost of goods</div><div class="fw-semibold">{{ $money($cogs) }}</div></div>
                    <div class="col-6"><div class="text-muted">Gross product profit</div><div class="fw-semibold {{ (float) $grossProductProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($grossProductProfit) }}</div></div>
                </div>
            </div>
        </div>
    </div>

    @if ($pendingApprovals > 0)
        <a href="{{ route('admin.expense-approvals.index') }}" class="alert alert-warning d-flex align-items-center gap-2 text-decoration-none">
            <i class="hgi-stroke hgi-alert-02"></i>
            <span><strong>{{ $pendingApprovals }}</strong> expense(s) awaiting approval.</span>
        </a>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">Revenue vs Expenses — 12 Months</div>
                <div class="card-body"><canvas id="finTrend" height="110"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">Revenue by Payment Method</div>
                <ul class="list-group list-group-flush">
                    @forelse ($revenueByMethod as $row)
                        <li class="list-group-item d-flex justify-content-between"><span>{{ $row->name }}</span><span class="fw-semibold">{{ $money($row->total) }}</span></li>
                    @empty
                        <li class="list-group-item text-muted">No payments in this period.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                    <span>Recent Payments</span><a href="{{ route('admin.payments.index') }}" class="small">View all</a>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($recentPayments as $payment)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('admin.payments.show', $payment) }}">{{ $payment->payment_reference }}</a>
                                <div class="small text-muted">{{ $payment->patient?->full_name }} · {{ $payment->payment_method }}</div>
                            </div>
                            <span class="fw-semibold">{{ $money($payment->amount) }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No payments yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                    <span>Recent Invoices</span><a href="{{ route('admin.invoices.index') }}" class="small">View all</a>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($recentInvoices as $invoice)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('admin.invoices.show', $invoice) }}">{{ $invoice->invoice_reference }}</a>
                                <div class="small text-muted">{{ $invoice->patient?->full_name }}</div>
                            </div>
                            <span class="badge-status status-{{ $invoice->status }}">{{ str_replace('_', ' ', $invoice->status) }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No invoices yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const trend = @json($trend);
                new Chart(document.getElementById('finTrend'), {
                    type: 'bar',
                    data: {
                        labels: trend.map(t => t.label),
                        datasets: [
                            { label: 'Revenue', data: trend.map(t => t.revenue), backgroundColor: '#02481f', borderRadius: 4 },
                            { label: 'Expenses', data: trend.map(t => t.expenses), backgroundColor: '#dc3545', borderRadius: 4 },
                        ],
                    },
                    options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } },
                });
            });
        </script>
    @endpush
</x-app-layout>
