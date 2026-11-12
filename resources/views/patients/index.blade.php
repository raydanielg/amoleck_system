<x-app-layout>
    <x-slot name="header">{{ __('Patients') }}</x-slot>
    <x-slot name="headerActions">
        <button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#patientDrawer">
            <i class="hgi-stroke hgi-plus-sign me-1"></i>{{ __('Add Patient') }}
        </button>
    </x-slot>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name, number or phone...">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="archived" @selected(request('status') === 'archived')>Archived</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100"><i class="hgi-stroke hgi-search-01 me-1"></i>Search</button>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Patient No</th><th>Full Name</th><th>Sex</th><th>Age</th>
                        <th>Phone</th><th>Visits</th><th>Registered</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($patients as $patient)
                        <tr>
                            <td class="fw-semibold">{{ $patient->patient_number }}</td>
                            <td><a href="{{ route('patients.show', $patient) }}">{{ $patient->full_name }}</a></td>
                            <td>{{ ucfirst($patient->sex ?? '—') }}</td>
                            <td>{{ $patient->age ?? '—' }}</td>
                            <td>{{ $patient->phone ?? '—' }}</td>
                            <td>{{ $patient->visits_count }}</td>
                            <td>{{ $patient->created_at->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge {{ $patient->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ ucfirst($patient->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('patients.show', $patient) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No patients found. <a href="{{ route('patients.create') }}">Register the first patient</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($patients->hasPages())
            <div class="card-footer bg-white">{{ $patients->links() }}</div>
        @endif
    </div>

    {{-- Add Patient Drawer --}}
    <div class="offcanvas offcanvas-end drawer" tabindex="-1" id="patientDrawer" data-bs-scroll="true" style="width: 560px;">
        <div class="offcanvas-header border-bottom">
            <h6 class="offcanvas-title fw-semibold">Register Patient</h6>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <form method="POST" action="{{ route('patients.store') }}" class="d-flex flex-column h-100">
            @csrf
            <div class="offcanvas-body">
                @include('patients._form', ['patient' => null])
            </div>
            <div class="border-top p-3 d-flex gap-2 bg-white sticky-bottom">
                <button type="submit" class="btn btn-primary flex-grow-1">Save Patient</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancel</button>
            </div>
        </form>
    </div>
</x-app-layout>
