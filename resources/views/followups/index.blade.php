<x-app-layout>
    <x-slot name="header">{{ __('Follow-ups') }}</x-slot>
    <x-slot name="headerActions">
        <button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#followUpDrawer">
            <i class="hgi-stroke hgi-plus-sign me-1"></i> New Follow-up
        </button>
    </x-slot>

    @if ($dueTodayCount > 0)
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="hgi-stroke hgi-alert-02"></i>
            <span><strong>{{ $dueTodayCount }}</strong> follow-up(s) due ({{ $overdueCount }} overdue).</span>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4"><input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search patient..."></div>
                <div class="col-auto">
                    <select name="due" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="today" @selected(request('due') === 'today')>Due now</option>
                        <option value="upcoming" @selected(request('due') === 'upcoming')>Upcoming</option>
                        <option value="overdue" @selected(request('due') === 'overdue')>Overdue</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All statuses</option>
                        @foreach (['pending', 'returned', 'missed', 'cancelled'] as $s)
                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto"><button class="btn btn-outline-secondary btn-sm">Filter</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Patient</th><th>Due Date</th><th>Visit</th><th>Notes</th><th>Recorded By</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($followUps as $followUp)
                        <tr class="{{ $followUp->isOverdue() ? 'table-danger' : '' }}">
                            <td><a href="{{ route('patients.show', $followUp->patient) }}">{{ $followUp->patient?->full_name }}</a><div class="small text-muted">{{ $followUp->patient?->patient_number }}</div></td>
                            <td class="{{ $followUp->isOverdue() ? 'text-danger fw-semibold' : '' }}">{{ $followUp->due_date->format('d/m/Y') }}</td>
                            <td>@if ($followUp->visit)<a href="{{ route('visits.show', $followUp->visit) }}">{{ $followUp->visit->visit_reference }}</a>@else — @endif</td>
                            <td class="small">{{ Str::limit($followUp->notes, 60) }}</td>
                            <td>{{ $followUp->recorder?->name }}</td>
                            <td><span class="badge-status status-{{ $followUp->status === 'returned' ? 'paid' : ($followUp->status === 'pending' ? 'pending' : $followUp->status) }}">{{ $followUp->status }}</span></td>
                            <td class="text-end">
                                @if ($followUp->status === 'pending')
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Update</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @foreach (['returned' => 'Patient Returned', 'missed' => 'Missed', 'cancelled' => 'Cancelled'] as $s => $label)
                                                <li>
                                                    <form method="POST" action="{{ route('follow-ups.status', $followUp) }}">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="status" value="{{ $s }}">
                                                        <button class="dropdown-item">{{ $label }}</button>
                                                    </form>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No follow-ups found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($followUps->hasPages())<div class="card-footer bg-white">{{ $followUps->links() }}</div>@endif
    </div>

    <div class="offcanvas offcanvas-end drawer" tabindex="-1" id="followUpDrawer" data-bs-scroll="true">
        <div class="offcanvas-header border-bottom">
            <h6 class="offcanvas-title fw-semibold">New Follow-up</h6>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <form method="POST" action="{{ route('follow-ups.store') }}" class="d-flex flex-column h-100">
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
                    <label class="form-label">Due Date <span class="text-danger">*</span></label>
                    <input type="date" name="due_date" class="form-control" required>
                </div>
                <div class="mb-0">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="3" class="form-control" placeholder="Reason for follow-up..."></textarea>
                </div>
            </div>
            <div class="border-top p-3 d-flex gap-2 bg-white sticky-bottom">
                <button type="submit" class="btn btn-primary flex-grow-1">Save</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancel</button>
            </div>
        </form>
    </div>
</x-app-layout>
