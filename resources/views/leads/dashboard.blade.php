<x-app-layout>
    <x-slot name="header">{{ __('Sponsored Ads Leads') }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('leads.reports', request()->query()) }}" class="btn btn-sm btn-outline-secondary"><i class="hgi-stroke hgi-analytics-01 me-1"></i>Reports</a>
        @if (auth()->user()->hasPermission('leads.create'))
            <a href="{{ route('leads.create') }}" class="btn btn-sm btn-primary"><i class="hgi-stroke hgi-plus-sign me-1"></i>New Lead</a>
        @endif
    </x-slot>

    <form method="GET" class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2 d-flex flex-wrap gap-2 align-items-center">
            @foreach (['today' => 'Today', 'yesterday' => 'Yesterday', '7d' => 'Last 7 Days', '30d' => 'Last 30 Days', 'month' => 'This Month', 'last_month' => 'Last Month', 'custom' => 'Custom'] as $value => $label)
                <a href="{{ route('leads.dashboard', ['range' => $value]) }}" class="btn btn-sm {{ $range === $value ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
            @endforeach
            @if ($range === 'custom')
                <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm" style="width:auto">
                <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm" style="width:auto">
                <input type="hidden" name="range" value="custom">
                <button class="btn btn-sm btn-primary">Apply</button>
            @endif
            <span class="ms-auto small text-muted">{{ $from }} → {{ $to }}</span>
        </div>
    </form>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Total Leads</div><div class="stat-value">{{ $stats['total'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Contacted</div><div class="stat-value text-success">{{ $stats['contacted'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Not Contacted</div><div class="stat-value text-warning">{{ $stats['not_contacted'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Converted</div><div class="stat-value text-primary">{{ $stats['converted'] }} <small class="fs-6">({{ $stats['conversion_rate'] }}%)</small></div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Interested</div><div class="stat-value">{{ $stats['interested'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Expected Visitors</div><div class="stat-value">{{ $stats['expected'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Confirmed</div><div class="stat-value">{{ $stats['confirmed'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Actually Visited</div><div class="stat-value text-success">{{ $stats['visited'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Assigned</div><div class="stat-value">{{ $stats['assigned'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Unassigned</div><div class="stat-value {{ $stats['unassigned'] ? 'text-danger' : '' }}">{{ $stats['unassigned'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Needs Info</div><div class="stat-value">{{ $stats['needs_info'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Not Interested</div><div class="stat-value text-muted">{{ $stats['not_interested'] }}</div></div></div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
            <span>Follow-ups Due / Overdue</span>
            <a href="{{ route('leads.index') }}" class="btn btn-sm btn-outline-primary">All Leads</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Lead</th><th>Phone</th><th>Manager</th><th>Next Follow-up</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($dueToday as $lead)
                        <tr>
                            <td class="fw-semibold">{{ $lead->full_name }} <span class="text-muted small">{{ $lead->lead_reference }}</span></td>
                            <td>{{ $lead->phone }}</td>
                            <td class="small">{{ $lead->assignee->name ?? '—' }}</td>
                            <td><span class="badge {{ $lead->followUpOverdue() ? 'text-bg-danger' : 'text-bg-warning' }}">{{ $lead->next_followup_at->format('d M Y') }}</span></td>
                            <td><span class="badge text-bg-light border">{{ \App\Models\Lead::VISIT_LABELS[$lead->visit_status] ?? $lead->visit_status }}</span></td>
                            <td class="text-end"><a href="{{ route('leads.show', $lead) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No follow-ups due. {{ $stats['unassigned'] ? $stats['unassigned'].' lead(s) still unassigned.' : '' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
