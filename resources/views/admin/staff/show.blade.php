@php $cur = \App\Support\Settings::get('currency', 'TZS'); @endphp
<x-app-layout>
    <x-slot name="header">{{ $staff->name }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary btn-sm"><i class="hgi-stroke hgi-arrow-left-01 me-1"></i> Staff</a>
        <a href="{{ route('admin.users.edit', $staff) }}" class="btn btn-outline-primary btn-sm">Edit Account</a>
    </x-slot>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Patients Registered</div><div class="stat-value">{{ number_format($patientsTotal) }}</div><div class="small text-muted">This month: {{ $patientsMonth }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Visits Recorded</div><div class="stat-value">{{ number_format($visitsTotal) }}</div><div class="small text-muted">This month: {{ $visitsMonth }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Expenses Submitted</div><div class="stat-value">{{ number_format($expensesTotal) }}</div><div class="small text-muted">This month: {{ $expensesMonth }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Approved Expenses (Month)</div><div class="stat-value">{{ $cur }} {{ number_format((float) $expenseAmountMonth) }}</div></div></div>
    </div>

    <div class="card shadow-sm border-0 mb-3" style="max-width: 800px;">
        <div class="card-body small">
            <strong>Account:</strong> {{ $staff->email }} ·
            <span class="badge-status {{ $staff->status === 'active' ? 'status-paid' : 'status-voided' }}">{{ $staff->status }}</span> ·
            Joined {{ $staff->created_at->format('d/m/Y') }} ·
            Last login {{ $staff->last_login_at?->format('d/m/Y H:i') ?? 'never' }} ·
            Last activity {{ $lastActivity?->created_at?->format('d/m/Y H:i') ?? 'none' }}
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">Recent Patients</div>
                <ul class="list-group list-group-flush">
                    @forelse ($recentPatients as $patient)
                        <li class="list-group-item d-flex justify-content-between"><a href="{{ route('patients.show', $patient) }}">{{ $patient->full_name }}</a><span class="small text-muted">{{ $patient->created_at->format('d/m/Y') }}</span></li>
                    @empty
                        <li class="list-group-item text-muted small">None.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">Recent Visits</div>
                <ul class="list-group list-group-flush">
                    @forelse ($recentVisits as $visit)
                        <li class="list-group-item d-flex justify-content-between"><a href="{{ route('visits.show', $visit) }}">{{ $visit->visit_reference }}</a><span class="small text-muted">{{ $visit->visit_datetime->format('d/m/Y') }}</span></li>
                    @empty
                        <li class="list-group-item text-muted small">None.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">Recent Expenses</div>
                <ul class="list-group list-group-flush">
                    @forelse ($recentExpenses as $expense)
                        <li class="list-group-item d-flex justify-content-between"><span>{{ $expense->title }}</span><span class="small"><span class="badge-status status-{{ $expense->status }}">{{ $expense->status }}</span></span></li>
                    @empty
                        <li class="list-group-item text-muted small">None.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
