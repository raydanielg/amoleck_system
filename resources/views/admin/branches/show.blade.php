<x-app-layout>
    <x-slot name="header">{{ $branch->name }} <span class="text-muted small">{{ $branch->code }}</span></x-slot>
    <x-slot name="headerActions"><a href="{{ route('admin.branches.index') }}" class="btn btn-outline-secondary btn-sm">All Branches</a></x-slot>
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Revenue</div><div class="stat-value">{{ number_format($stats['revenue']) }}</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Expenses</div><div class="stat-value text-danger">{{ number_format($stats['expenses']) }}</div></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Patients</div><div class="stat-value">{{ $stats['patients'] }}</div></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Staff</div><div class="stat-value">{{ $stats['employees'] }}</div></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Assets</div><div class="stat-value">{{ $stats['assets'] }}</div></div></div>
    </div>
    <div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">Branch Staff</div>
        <div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>Employee</th><th>Department</th><th>Type</th><th>Status</th></tr></thead>
        <tbody>@forelse ($branch->employees as $e)<tr><td>{{ $e->full_name }}<div class="small text-muted">{{ $e->employee_number }}</div></td><td>{{ $e->department?->name ?? '—' }}</td><td>{{ $e->employment_type }}</td><td><span class="badge {{ $e->status==='active'?'text-bg-success':'text-bg-secondary' }}">{{ $e->status }}</span></td></tr>
        @empty<tr><td colspan="4" class="text-center text-muted py-3">No staff assigned.</td></tr>@endforelse</tbody></table></div>
    </div>
</x-app-layout>
