<x-app-layout>
    <x-slot name="header">{{ __('Visits') }}</x-slot>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search reference or patient...">
                </div>
                <div class="col-md-2">
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach (['draft', 'completed', 'voided'] as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100"><i class="hgi-stroke hgi-search-01 me-1"></i>Filter</button>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Reference</th><th>Patient</th><th>Date</th><th>Services</th><th>Recorded By</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($visits as $visit)
                        <tr>
                            <td class="fw-semibold">{{ $visit->visit_reference }}</td>
                            <td><a href="{{ route('patients.show', $visit->patient) }}">{{ $visit->patient?->full_name }}</a></td>
                            <td>{{ $visit->visit_datetime->format('d/m/Y H:i') }}</td>
                            <td class="small">{{ $visit->services->pluck('service_name_snapshot')->implode(', ') ?: '—' }}</td>
                            <td class="small">{{ $visit->recorder?->name }}</td>
                            <td>
                                <span class="badge {{ ['completed' => 'text-bg-success', 'draft' => 'text-bg-warning', 'voided' => 'text-bg-secondary'][$visit->status] ?? 'text-bg-light' }}">
                                    {{ ucfirst($visit->status) }}
                                </span>
                            </td>
                            <td class="text-end"><a href="{{ route('visits.show', $visit) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No visits found. Open a patient profile to create a visit.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($visits->hasPages())
            <div class="card-footer bg-white">{{ $visits->links() }}</div>
        @endif
    </div>
</x-app-layout>
