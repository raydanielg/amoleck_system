<x-app-layout>
    <x-slot name="header">{{ __('Employees') }}</x-slot>
    <x-slot name="headerActions">
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#deptDrawer">Departments</button>
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#attDrawer">Attendance</button>
        <button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#empDrawer"><i class="hgi-stroke hgi-plus-sign me-1"></i>New Employee</button>
    </x-slot>
    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom"><form method="GET" class="row g-2">
            <div class="col-md-5"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Name or employee number..."></div>
            <div class="col-md-3"><select name="status" class="form-select"><option value="">All statuses</option>@foreach (['active','suspended','terminated','resigned'] as $st)<option @selected(request('status')===$st)>{{ $st }}</option>@endforeach</select></div>
            <div class="col-md-3"><select name="department_id" class="form-select"><option value="">All departments</option>@foreach ($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id')==$d->id)>{{ $d->name }}</option>@endforeach</select></div>
            <div class="col-md-1"><button class="btn btn-outline-secondary w-100">Go</button></div>
        </form></div>
        <div class="table-responsive"><table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Emp #</th><th>Name</th><th>Department</th><th>Position</th><th>Branch</th><th>Type</th><th>Status</th><th></th></tr></thead>
            <tbody>@forelse ($employees as $e)
                <tr>
                    <td class="small text-muted">{{ $e->employee_number }}</td>
                    <td class="fw-semibold">{{ $e->full_name }}<div class="small text-muted fw-normal">{{ $e->phone }}</div></td>
                    <td>{{ $e->department?->name ?? '—' }}</td><td>{{ $e->jobPosition?->title ?? '—' }}</td>
                    <td>{{ $e->branch?->name ?? '—' }}</td><td><span class="badge text-bg-light border">{{ $e->employment_type }}</span></td>
                    <td><span class="badge {{ $e->status==='active'?'text-bg-success':'text-bg-secondary' }}">{{ $e->status }}</span></td>
                    <td class="text-end"><a href="{{ route('admin.employees.show', $e) }}" class="btn btn-sm btn-outline-primary">Profile</a></td>
                </tr>
            @empty<tr><td colspan="8" class="text-center text-muted py-4">No employees.</td></tr>@endforelse</tbody>
        </table></div>
        @if ($employees->hasPages())<div class="card-footer bg-white">{{ $employees->links() }}</div>@endif
    </div>
    <div class="offcanvas offcanvas-end" id="empDrawer" style="width:520px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Register Employee</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('admin.employees.store') }}" class="offcanvas-body d-flex flex-column row g-2">
            @csrf
            <div class="col-12"><label class="form-label">Full Name *</label><input name="full_name" class="form-control" required></div>
            <div class="col-6"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
            <div class="col-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
            <div class="col-6"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">—</option>@foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
            <div class="col-6"><label class="form-label">Position</label><select name="job_position_id" class="form-select"><option value="">—</option>@foreach ($positions as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach</select></div>
            <div class="col-6"><label class="form-label">Employment Type *</label><select name="employment_type" class="form-select"><option value="full_time">Full time</option><option value="part_time">Part time</option><option value="contract">Contract</option><option value="intern">Intern</option></select></div>
            <div class="col-6"><label class="form-label">Branch</label><select name="branch_id" class="form-select"><option value="">—</option>@foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
            <div class="col-6"><label class="form-label">Hired Date</label><input type="date" name="hired_at" class="form-control"></div>
            <div class="col-6"><label class="form-label">Basic Salary</label><input type="number" step="0.01" name="basic_salary" class="form-control"></div>
            <div class="col-6"><label class="form-label">Contract Start</label><input type="date" name="contract_start" class="form-control"></div>
            <div class="col-6"><label class="form-label">Contract End</label><input type="date" name="contract_end" class="form-control"></div>
            <div class="col-12"><label class="form-label">Bank Details <span class="text-muted small">(encrypted)</span></label><input name="bank_details" class="form-control" placeholder="Bank, account no."></div>
            <div class="col-12"><label class="form-label">Emergency Contact</label><input name="emergency_contact" class="form-control"></div>
            <div class="col-12 mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Register</button></div>
        </form>
    </div>
    <div class="offcanvas offcanvas-end" id="deptDrawer" style="width:380px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Departments &amp; Positions</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <div class="offcanvas-body">
            <form method="POST" action="{{ route('admin.departments.store') }}" class="d-flex gap-2 mb-2">@csrf<input name="name" class="form-control" placeholder="New department" required><button class="btn btn-outline-primary">Add</button></form>
            <ul class="list-group mb-4">@foreach ($departments as $d)<li class="list-group-item">{{ $d->name }}</li>@endforeach</ul>
            <form method="POST" action="{{ route('admin.positions.store') }}" class="d-flex gap-2 mb-2">@csrf<input name="title" class="form-control" placeholder="New position" required><button class="btn btn-outline-primary">Add</button></form>
            <ul class="list-group">@foreach ($positions as $p)<li class="list-group-item">{{ $p->title }}</li>@endforeach</ul>
        </div>
    </div>
    <div class="offcanvas offcanvas-end" id="attDrawer" style="width:380px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Record Attendance</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('admin.attendance.store') }}" class="offcanvas-body d-flex flex-column gap-2">
            @csrf
            <label class="form-label">Employee</label><select name="employee_id" class="form-select" required>@foreach ($employees->isEmpty() ? [] : $employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select>
            <label class="form-label">Date</label><input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
            <div class="row g-2"><div class="col-6"><label class="form-label">Check-in</label><input type="time" name="check_in" class="form-control"></div><div class="col-6"><label class="form-label">Check-out</label><input type="time" name="check_out" class="form-control"></div></div>
            <label class="form-label">Status</label><select name="status" class="form-select"><option value="present">Present</option><option value="late">Late</option><option value="absent">Absent</option><option value="leave">Leave</option><option value="half_day">Half day</option></select>
            <input name="notes" class="form-control" placeholder="Notes">
            <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Save</button></div>
        </form>
    </div>
</x-app-layout>
