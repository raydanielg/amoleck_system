@php
    $cur = \App\Support\Settings::get('currency', 'TZS');
    $money = fn ($v) => $cur.' '.number_format((float) $v);
    $isAdmin = Auth::user()->isAdmin();
    $periods = [
        'today' => 'Today', 'yesterday' => 'Yesterday', '7d' => '7 Days', '30d' => '30 Days',
        'month' => 'This Month', 'last_month' => 'Last Month', 'quarter' => 'This Quarter', 'year' => 'This Year',
    ];
@endphp
<x-app-layout>
    <x-slot name="header">{{ __('Dashboard') }}</x-slot>
    <x-slot name="headerActions">
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                {{ $periods[$period] ?? 'This Month' }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach ($periods as $key => $label)
                    <li><a class="dropdown-item {{ $period === $key ? 'active' : '' }}" href="{{ route('dashboard', ['period' => $key]) }}">{{ $label }}</a></li>
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

    @if ($isAdmin)
        {{-- Financial KPIs --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="stat-card d-flex align-items-center gap-3">
                    <span class="stat-icon stat-icon-tint-green"><i class="hgi-stroke hgi-coins-01"></i></span>
                    <div>
                        <div class="stat-label">Collected Revenue</div>
                        <div class="stat-value">{{ $money($collected ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card d-flex align-items-center gap-3">
                    <span class="stat-icon stat-icon-tint-blue"><i class="hgi-stroke hgi-invoice-01"></i></span>
                    <div>
                        <div class="stat-label">Billed (Invoices)</div>
                        <div class="stat-value">{{ $money($billed ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card d-flex align-items-center gap-3">
                    <span class="stat-icon stat-icon-tint-amber"><i class="hgi-stroke hgi-clock-01"></i></span>
                    <div>
                        <div class="stat-label">Outstanding</div>
                        <div class="stat-value">{{ $money($outstanding ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card d-flex align-items-center gap-3">
                    <span class="stat-icon stat-icon-tint-red"><i class="hgi-stroke hgi-money-02"></i></span>
                    <div>
                        <div class="stat-label">Expenses</div>
                        <div class="stat-value">{{ $money($expensesPeriod) }}</div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-label">Total Patients</div><div class="stat-value">{{ number_format($totalPatients) }}</div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-label">New Patients (Month)</div><div class="stat-value">{{ number_format($newPatientsMonth) }}</div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-label">Visits (Month)</div><div class="stat-value">{{ number_format($visitsMonth) }}</div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card"><div class="stat-label">Expenses (Month)</div><div class="stat-value">{{ $money($expensesMonth) }}</div></div>
            </div>
        </div>
    @endif

    {{-- Operational KPIs --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 col-lg-2"><div class="stat-card"><div class="stat-label">Active Patients</div><div class="stat-value">{{ number_format($activePatients) }}</div></div></div>
        <div class="col-6 col-md-3 col-lg-2"><div class="stat-card"><div class="stat-label">New Patients</div><div class="stat-value">{{ number_format($newPatientsMonth) }}</div><div class="small text-muted">Today: {{ $newPatientsToday }}</div></div></div>
        <div class="col-6 col-md-3 col-lg-2"><div class="stat-card"><div class="stat-label">Visits Today</div><div class="stat-value">{{ number_format($visitsToday) }}</div></div></div>
        <div class="col-6 col-md-3 col-lg-2"><div class="stat-card"><div class="stat-label">Completed</div><div class="stat-value">{{ number_format($visitsCompleted) }}</div><div class="small text-muted">Draft: {{ $visitsDraft }} · Void: {{ $visitsVoided }}</div></div></div>
        <div class="col-6 col-md-3 col-lg-2"><div class="stat-card"><div class="stat-label">Services Today</div><div class="stat-value">{{ number_format($servicesToday) }}</div></div></div>
        <div class="col-6 col-md-3 col-lg-2">
            <div class="stat-card">
                <div class="stat-label">Follow-ups Due</div>
                <div class="stat-value {{ $followUpsDue > 0 ? 'text-danger' : '' }}">{{ number_format($followUpsDue) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-lg-2"><div class="stat-card"><div class="stat-label">Upcoming Follow-ups</div><div class="stat-value">{{ number_format($patientsWithUpcomingFollowUps) }}</div></div></div>
        <div class="col-6 col-md-3 col-lg-2"><div class="stat-card"><div class="stat-label">No Visit (90d)</div><div class="stat-value {{ $patientsWithoutRecentVisits > 0 ? 'text-warning' : '' }}">{{ number_format($patientsWithoutRecentVisits) }}</div></div></div>
        @if ($isAdmin && $pendingExpenses > 0)
            <div class="col-12">
                <a href="{{ route('admin.expense-approvals.index') }}" class="alert alert-warning d-flex align-items-center gap-2 mb-0 text-decoration-none">
                    <i class="hgi-stroke hgi-alert-02"></i>
                    <span><strong>{{ $pendingExpenses }}</strong> expense(s) awaiting your approval.</span>
                </a>
            </div>
        @endif
    </div>

    @if ($isAdmin)
        {{-- Charts --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-semibold">Revenue vs Expenses — Last 12 Months</div>
                    <div class="card-body"><canvas id="revenueChart" height="110"></canvas></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-semibold">Expenses by Category</div>
                    <div class="card-body">
                        @if ($expensesByCategory->isEmpty())
                            <p class="text-muted small mb-0">No expense data in this period.</p>
                        @else
                            <canvas id="expenseCatChart" height="150"></canvas>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-semibold">Visits — Last 14 Days</div>
                    <div class="card-body"><canvas id="visitsChart" height="150"></canvas></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-semibold">Most Requested Services</div>
                    <div class="card-body">
                        @if ($servicePopularity->isEmpty())
                            <p class="text-muted small mb-0">No service usage yet.</p>
                        @else
                            <canvas id="servicesChart" height="150"></canvas>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-semibold">Revenue by Service</div>
                    <div class="card-body">
                        @if ($revenueByService->isEmpty())
                            <p class="text-muted small mb-0">No billing data yet.</p>
                        @else
                            <canvas id="revenueServiceChart" height="150"></canvas>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">Recent Patients</div>
                <ul class="list-group list-group-flush">
                    @forelse ($recentPatients as $patient)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="{{ route('patients.show', $patient) }}">{{ $patient->full_name }}</a>
                            <span class="text-muted small">{{ $patient->patient_number }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No patients yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">Recent Visits</div>
                <ul class="list-group list-group-flush">
                    @forelse ($recentVisits as $visit)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="{{ route('visits.show', $visit) }}">{{ $visit->visit_reference }}</a>
                            <span class="text-muted small">{{ $visit->patient?->full_name }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No visits yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                    <span>Upcoming Follow-ups</span>
                    <a href="{{ route('follow-ups.index') }}" class="small">View all</a>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($upcomingFollowUps as $followUp)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ $followUp->patient?->full_name }}</span>
                            <span class="small {{ $followUp->isOverdue() ? 'text-danger fw-semibold' : 'text-muted' }}">{{ $followUp->due_date->format('d/m/Y') }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No pending follow-ups.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    @if ($isAdmin)
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const trend = @json($trend);
                    new Chart(document.getElementById('revenueChart'), {
                        type: 'line',
                        data: {
                            labels: trend.map(t => t.label),
                            datasets: [
                                { label: 'Revenue', data: trend.map(t => t.revenue), borderColor: '#02481f', backgroundColor: 'rgba(2,72,31,0.08)', fill: true, tension: 0.3 },
                                { label: 'Expenses', data: trend.map(t => t.expenses), borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.06)', fill: true, tension: 0.3 },
                            ],
                        },
                        options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } },
                    });

                    @if ($expensesByCategory->isNotEmpty())
                    new Chart(document.getElementById('expenseCatChart'), {
                        type: 'doughnut',
                        data: {
                            labels: @json($expensesByCategory->pluck('name')),
                            datasets: [{ data: @json($expensesByCategory->pluck('total')->map(fn ($v) => (float) $v)), backgroundColor: ['#02481f', '#0d6efd', '#20c997', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#6c757d'] }],
                        },
                        options: { plugins: { legend: { position: 'bottom' } } },
                    });
                    @endif

                    const visitTrend = @json($visitTrend);
                    new Chart(document.getElementById('visitsChart'), {
                        type: 'bar',
                        data: { labels: visitTrend.map(t => t.label), datasets: [{ label: 'Visits', data: visitTrend.map(t => t.visits), backgroundColor: '#02481f', borderRadius: 4 }] },
                        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
                    });

                    @if ($servicePopularity->isNotEmpty())
                    new Chart(document.getElementById('servicesChart'), {
                        type: 'bar',
                        data: { labels: @json($servicePopularity->pluck('name')), datasets: [{ data: @json($servicePopularity->pluck('total')), backgroundColor: '#0d6efd', borderRadius: 4 }] },
                        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } },
                    });
                    @endif

                    @if ($revenueByService->isNotEmpty())
                    new Chart(document.getElementById('revenueServiceChart'), {
                        type: 'bar',
                        data: { labels: @json($revenueByService->pluck('name')), datasets: [{ data: @json($revenueByService->pluck('total')->map(fn ($v) => (float) $v)), backgroundColor: '#02481f', borderRadius: 4 }] },
                        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } },
                    });
                    @endif
                });
            </script>
        @endpush
    @endif
</x-app-layout>
