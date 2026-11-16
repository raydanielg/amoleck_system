<x-app-layout>
    <x-slot name="header">{{ __('All Leads') }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('leads.dashboard') }}" class="btn btn-sm btn-outline-secondary">Dashboard</a>
        @if (auth()->user()->hasPermission('leads.create'))
            <a href="{{ route('leads.create') }}" class="btn btn-sm btn-primary"><i class="hgi-stroke hgi-plus-sign me-1"></i>New Lead</a>
        @endif
    </x-slot>

    <form method="GET" class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2 row g-2 align-items-center">
            <div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search name, phone, reference"></div>
            <div class="col-md-2">
                <select name="visit_status" class="form-select form-select-sm">
                    <option value="">All visit statuses</option>
                    @foreach (\App\Models\Lead::VISIT_LABELS as $k => $label)<option value="{{ $k }}" @selected(request('visit_status') === $k)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="contact_status" class="form-select form-select-sm">
                    <option value="">All contact statuses</option>
                    <option value="contacted" @selected(request('contact_status') === 'contacted')>Contacted</option>
                    <option value="not_contacted" @selected(request('contact_status') === 'not_contacted')>Not contacted</option>
                    <option value="new" @selected(request('contact_status') === 'new')>New</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="lead_source_id" class="form-select form-select-sm">
                    <option value="">All sources</option>
                    @foreach ($sources as $src)<option value="{{ $src->id }}" @selected(request('lead_source_id') == $src->id)>{{ $src->name }}</option>@endforeach
                </select>
            </div>
            @if ($managesAll)
                <div class="col-md-2">
                    <select name="assigned_to" class="form-select form-select-sm">
                        <option value="">All managers</option>
                        @foreach ($managers as $m)<option value="{{ $m->id }}" @selected(request('assigned_to') == $m->id)>{{ $m->name }}</option>@endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-1"><button class="btn btn-sm btn-primary w-100">Filter</button></div>
        </div>
    </form>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Lead</th><th>Phone</th><th>Source</th><th>Received</th><th>Interest</th><th>Visit Status</th><th>Manager</th><th></th></tr></thead>
                <tbody>
                    @forelse ($leads as $lead)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $lead->full_name }}</div>
                                <div class="small text-muted">{{ $lead->lead_reference }}{{ $lead->isConverted() ? ' · '.$lead->patient->patient_number : '' }}</div>
                            </td>
                            <td>{{ $lead->phone }}</td>
                            <td class="small">{{ $lead->source->name ?? '—' }}</td>
                            <td class="small">{{ $lead->received_at->format('d M Y') }}</td>
                            <td>
                                @if ($lead->interest_level)
                                    <span class="badge {{ ['interested' => 'text-bg-success', 'needs_info' => 'text-bg-warning', 'not_interested' => 'text-bg-secondary'][$lead->interest_level] ?? 'text-bg-light border' }}">{{ str_replace('_', ' ', $lead->interest_level) }}</span>
                                @else <span class="text-muted small">—</span> @endif
                            </td>
                            <td><span class="badge text-bg-light border">{{ \App\Models\Lead::VISIT_LABELS[$lead->visit_status] ?? $lead->visit_status }}</span></td>
                            <td class="small">{{ $lead->assignee->name ?? '—' }}</td>
                            <td class="text-end"><a href="{{ route('leads.show', $lead) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No leads found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($leads->hasPages())<div class="card-footer bg-white">{{ $leads->links() }}</div>@endif
    </div>
</x-app-layout>
