@php $cur = \App\Support\Settings::get('currency', 'TZS'); @endphp
<x-app-layout>
    <x-slot name="header">{{ $service->name }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('admin.services.index') }}" class="btn btn-outline-secondary btn-sm"><i class="hgi-stroke hgi-arrow-left-01 me-1"></i> Services</a>
        <a href="{{ route('admin.services.edit', $service) }}" class="btn btn-primary btn-sm">Edit</a>
    </x-slot>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Current Price</div><div class="stat-value">{{ $cur }} {{ number_format((float) $service->standard_price) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Times Provided</div><div class="stat-value">{{ number_format($usageCount) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Billed Revenue</div><div class="stat-value">{{ $cur }} {{ number_format((float) $billedRevenue) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Status</div><div class="stat-value"><span class="badge-status {{ $service->is_active ? 'status-paid' : 'status-voided' }}">{{ $service->is_active ? 'Active' : 'Inactive' }}</span></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">Details</div>
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-4 text-muted">Code</dt><dd class="col-8">{{ $service->service_code ?? '—' }}</dd>
                        <dt class="col-4 text-muted">Category</dt><dd class="col-8">{{ $service->category?->name ?? '—' }}</dd>
                        <dt class="col-4 text-muted">Duration</dt><dd class="col-8">{{ $service->duration_minutes ? $service->duration_minutes.' min' : '—' }}</dd>
                        <dt class="col-4 text-muted">Notes Required</dt><dd class="col-8">{{ $service->requires_notes ? 'Yes' : 'No' }}</dd>
                        <dt class="col-4 text-muted">Description</dt><dd class="col-8" style="white-space: pre-wrap;">{{ $service->description ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-semibold">Price History</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between small">
                        <span>Current: <strong>{{ $cur }} {{ number_format((float) $service->standard_price) }}</strong></span>
                        <span class="text-muted">now</span>
                    </li>
                    @forelse ($service->priceHistories as $history)
                        <li class="list-group-item d-flex justify-content-between small">
                            <span>{{ $cur }} {{ number_format((float) $history->old_price) }} → <strong>{{ $cur }} {{ number_format((float) $history->new_price) }}</strong></span>
                            <span class="text-muted">{{ $history->changer?->name }} · {{ $history->created_at->format('d/m/Y') }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted small">No price changes recorded.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-3">
        <div class="card-header bg-white fw-semibold">Recent Usage</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Visit</th><th>Patient</th><th>Date</th><th class="text-end">Price at Time</th></tr></thead>
                <tbody>
                    @forelse ($recentUsage as $usage)
                        <tr>
                            <td><a href="{{ route('visits.show', $usage->visit) }}">{{ $usage->visit?->visit_reference }}</a></td>
                            <td>{{ $usage->visit?->patient?->full_name }}</td>
                            <td>{{ $usage->visit?->visit_datetime?->format('d/m/Y') }}</td>
                            <td class="text-end">{{ $cur }} {{ number_format((float) $usage->price_snapshot) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Not used in any visit yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
