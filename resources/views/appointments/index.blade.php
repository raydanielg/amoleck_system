<x-app-layout>
    <x-slot name="header">{{ __('Appointments') }}</x-slot>
    <x-slot name="headerActions">
        <button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#appointmentDrawer">
            <i class="hgi-stroke hgi-plus-sign me-1"></i> New Appointment
        </button>
    </x-slot>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4"><input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search patient or reference..."></div>
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All statuses</option>
                        @foreach (['scheduled', 'confirmed', 'completed', 'cancelled', 'missed'] as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <select name="when" class="form-select form-select-sm">
                        <option value="">Any time</option>
                        <option value="upcoming" @selected(request('when') === 'upcoming')>Upcoming</option>
                        <option value="today" @selected(request('when') === 'today')>Today</option>
                        <option value="past" @selected(request('when') === 'past')>Past</option>
                    </select>
                </div>
                <div class="col-auto"><button class="btn btn-outline-secondary btn-sm">Filter</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Reference</th><th>Patient</th><th>Service</th><th>Staff</th><th>Scheduled</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($appointments as $appointment)
                        <tr>
                            <td>{{ $appointment->appointment_reference }}</td>
                            <td><a href="{{ route('patients.show', $appointment->patient) }}">{{ $appointment->patient?->full_name }}</a></td>
                            <td>{{ $appointment->service?->name ?? '—' }}</td>
                            <td>{{ $appointment->staff?->name ?? '—' }}</td>
                            <td>{{ $appointment->scheduled_at->format('d/m/Y H:i') }}</td>
                            <td><span class="badge-status status-{{ $appointment->status }}">{{ $appointment->status }}</span></td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Actions</button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @foreach (['confirmed', 'completed', 'missed', 'cancelled'] as $s)
                                            @if ($appointment->status !== $s)
                                                <li>
                                                    <form method="POST" action="{{ route('appointments.status', $appointment) }}">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="status" value="{{ $s }}">
                                                        <button class="dropdown-item">Mark {{ ucfirst($s) }}</button>
                                                    </form>
                                                </li>
                                            @endif
                                        @endforeach
                                        <li><hr class="dropdown-divider"></li>
                                        <li><button class="dropdown-item" data-bs-toggle="offcanvas" data-bs-target="#editDrawer{{ $appointment->id }}">Edit</button></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No appointments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($appointments->hasPages())<div class="card-footer bg-white">{{ $appointments->links() }}</div>@endif
    </div>

    {{-- Create Drawer --}}
    <div class="offcanvas offcanvas-end drawer" tabindex="-1" id="appointmentDrawer" data-bs-scroll="true">
        <div class="offcanvas-header border-bottom">
            <h6 class="offcanvas-title fw-semibold">New Appointment</h6>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <form method="POST" action="{{ route('appointments.store') }}" class="d-flex flex-column h-100">
            @csrf
            <div class="offcanvas-body">
                <div class="mb-3">
                    <label class="form-label">Patient <span class="text-danger">*</span></label>
                    <select name="patient_id" class="form-select" required>
                        <option value="">Select patient...</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}">{{ $patient->full_name }} ({{ $patient->patient_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Service</label>
                    <select name="service_id" class="form-select">
                        <option value="">—</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Assigned Staff</label>
                    <select name="staff_id" class="form-select">
                        <option value="">—</option>
                        @foreach ($staff as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date & Time <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="scheduled_at" class="form-control" required>
                </div>
                <div class="mb-0">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="2" class="form-control"></textarea>
                </div>
            </div>
            <div class="border-top p-3 d-flex gap-2 bg-white sticky-bottom">
                <button type="submit" class="btn btn-primary flex-grow-1">Schedule</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancel</button>
            </div>
        </form>
    </div>

    {{-- Edit Drawers --}}
    @foreach ($appointments as $appointment)
        <div class="offcanvas offcanvas-end drawer" tabindex="-1" id="editDrawer{{ $appointment->id }}" data-bs-scroll="true">
            <div class="offcanvas-header border-bottom">
                <h6 class="offcanvas-title fw-semibold">Edit {{ $appointment->appointment_reference }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <form method="POST" action="{{ route('appointments.update', $appointment) }}" class="d-flex flex-column h-100">
                @csrf @method('PUT')
                <div class="offcanvas-body">
                    <div class="mb-3">
                        <label class="form-label">Patient</label>
                        <select name="patient_id" class="form-select" required>
                            @foreach ($patients as $patient)
                                <option value="{{ $patient->id }}" @selected($appointment->patient_id === $patient->id)>{{ $patient->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Service</label>
                        <select name="service_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}" @selected($appointment->service_id === $service->id)>{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assigned Staff</label>
                        <select name="staff_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($staff as $member)
                                <option value="{{ $member->id }}" @selected($appointment->staff_id === $member->id)>{{ $member->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date & Time</label>
                        <input type="datetime-local" name="scheduled_at" value="{{ $appointment->scheduled_at->format('Y-m-d\TH:i') }}" class="form-control" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-control">{{ $appointment->notes }}</textarea>
                    </div>
                </div>
                <div class="border-top p-3 d-flex gap-2 bg-white sticky-bottom">
                    <button type="submit" class="btn btn-primary flex-grow-1">Save</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancel</button>
                </div>
            </form>
        </div>
    @endforeach
</x-app-layout>
