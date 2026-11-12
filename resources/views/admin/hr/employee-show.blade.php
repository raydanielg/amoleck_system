<x-app-layout>
    <x-slot name="header">{{ $employee->full_name }} <span class="text-muted small">{{ $employee->employee_number }}</span></x-slot>
    <x-slot name="headerActions"><a href="{{ route('admin.employees.index') }}" class="btn btn-outline-secondary btn-sm">All Employees</a></x-slot>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-3"><div class="card-header bg-white fw-semibold">Employment</div><div class="card-body">
                <form method="POST" action="{{ route('admin.employees.update', $employee) }}" class="row g-2">
                    @csrf @method('PUT')
                    <div class="col-md-6"><label class="form-label">Full Name</label><input name="full_name" class="form-control" value="{{ $employee->full_name }}" required></div>
                    <div class="col-md-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ $employee->phone }}"></div>
                    <div class="col-md-3"><label class="form-label">Email</label><input name="email" class="form-control" value="{{ $employee->email }}"></div>
                    <div class="col-md-4"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">—</option>@foreach (\App\Models\Department::where('is_active',true)->get() as $d)<option value="{{ $d->id }}" @selected($employee->department_id===$d->id)>{{ $d->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Position</label><select name="job_position_id" class="form-select"><option value="">—</option>@foreach (\App\Models\JobPosition::where('is_active',true)->get() as $p)<option value="{{ $p->id }}" @selected($employee->job_position_id===$p->id)>{{ $p->title }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Type</label><select name="employment_type" class="form-select">@foreach (['full_time','part_time','contract','intern'] as $t)<option @selected($employee->employment_type===$t)>{{ $t }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Branch</label><select name="branch_id" class="form-select"><option value="">—</option>@foreach (\App\Models\Branch::where('status','active')->get() as $b)<option value="{{ $b->id }}" @selected($employee->branch_id===$b->id)>{{ $b->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Basic Salary</label><input type="number" step="0.01" name="basic_salary" class="form-control" value="{{ $employee->basic_salary }}"></div>
                    <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select">@foreach (['active','suspended','terminated','resigned'] as $st)<option @selected($employee->status===$st)>{{ $st }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Contract Start</label><input type="date" name="contract_start" class="form-control" value="{{ $employee->contract_start?->toDateString() }}"></div>
                    <div class="col-md-4"><label class="form-label">Contract End</label><input type="date" name="contract_end" class="form-control" value="{{ $employee->contract_end?->toDateString() }}"></div>
                    <div class="col-md-4"><label class="form-label">Bank Details</label><input name="bank_details" class="form-control" value="" placeholder="{{ $employee->getAttributes()['bank_details'] ? '•••••• (saved)' : 'Not set' }}"></div>
                    <div class="col-12"><button class="btn btn-primary btn-sm">Save Changes</button></div>
                </form>
            </div></div>
            <div class="card shadow-sm border-0 mb-3"><div class="card-header bg-white fw-semibold d-flex justify-content-between">Leave Requests
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="offcanvas" data-bs-target="#leaveDrawer">+ Request</button></div>
                <div class="table-responsive"><table class="table table-sm mb-0"><thead class="table-light"><tr><th>Type</th><th>Dates</th><th>Days</th><th>Status</th><th></th></tr></thead>
                <tbody>@forelse ($employee->leaveRequests->sortByDesc('created_at') as $lr)
                    <tr><td>{{ $lr->leaveType->name }}</td><td>{{ $lr->start_date->format('d M') }} – {{ $lr->end_date->format('d M Y') }}</td><td>{{ $lr->days }}</td>
                    <td><span class="badge {{ ['approved'=>'text-bg-success','rejected'=>'text-bg-danger'][$lr->status] ?? 'text-bg-warning' }}">{{ $lr->status }}</span></td>
                    <td class="text-end">@if ($lr->status==='pending')
                        <form method="POST" action="{{ route('admin.leave.decide', $lr) }}" class="d-inline">@csrf<input type="hidden" name="decision" value="approved"><button class="btn btn-sm btn-outline-success">Approve</button></form>
                        <form method="POST" action="{{ route('admin.leave.decide', $lr) }}" class="d-inline">@csrf<input type="hidden" name="decision" value="rejected"><button class="btn btn-sm btn-outline-danger">Reject</button></form>
                    @endif</td></tr>
                @empty<tr><td colspan="5" class="text-center text-muted py-3">No leave requests.</td></tr>@endforelse</tbody></table></div>
            </div>
            <div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">Recent Attendance</div>
                <div class="table-responsive"><table class="table table-sm mb-0"><thead class="table-light"><tr><th>Date</th><th>In</th><th>Out</th><th>Status</th></tr></thead>
                <tbody>@forelse ($attendance as $a)<tr><td>{{ $a->date->format('d M Y') }}</td><td>{{ $a->check_in ?? '—' }}</td><td>{{ $a->check_out ?? '—' }}</td><td><span class="badge text-bg-light border">{{ $a->status }}</span></td></tr>
                @empty<tr><td colspan="4" class="text-center text-muted py-3">No records.</td></tr>@endforelse</tbody></table></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-3"><div class="card-header bg-white fw-semibold">Salary Components</div><div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Basic Salary</span><strong>{{ number_format((float) $employee->basic_salary) }}</strong></div>
                @foreach ($employee->components->where('is_active', true) as $c)
                    <div class="d-flex justify-content-between small"><span>{{ $c->name }} <span class="badge text-bg-light border">{{ $c->kind }}</span></span><span>{{ number_format((float) $c->amount) }}</span></div>
                @endforeach
                <form method="POST" action="{{ route('admin.employees.components.store', $employee) }}" class="border-top pt-2 mt-2 row g-1">
                    @csrf
                    <div class="col-5"><input name="name" class="form-control form-control-sm" placeholder="Name" required></div>
                    <div class="col-4"><input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="Amount" required></div>
                    <div class="col-3"><select name="kind" class="form-select form-select-sm"><option value="allowance">Allow.</option><option value="bonus">Bonus</option></select></div>
                    <div class="col-12"><button class="btn btn-sm btn-outline-primary w-100">Add Component</button></div>
                </form>
            </div></div>
            <div class="card shadow-sm border-0 mb-3"><div class="card-header bg-white fw-semibold">Loans &amp; Advances</div>
                <ul class="list-group list-group-flush small">@forelse ($employee->loans as $l)
                    <li class="list-group-item d-flex justify-content-between"><span>{{ $l->reference }} <span class="text-muted">({{ $l->kind }})</span></span><span>{{ number_format((float) $l->balance) }} <span class="badge {{ $l->status==='closed'?'text-bg-secondary':'text-bg-info' }}">{{ $l->status }}</span></span></li>
                @empty<li class="list-group-item text-muted">None.</li>@endforelse</ul>
            </div>
            <div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">Assigned Assets</div>
                <ul class="list-group list-group-flush small">@forelse ($employee->assets as $a)
                    <li class="list-group-item d-flex justify-content-between"><span>{{ $a->name }}</span><span class="text-muted">{{ $a->asset_number }}</span></li>
                @empty<li class="list-group-item text-muted">None.</li>@endforelse</ul>
            </div>
        </div>
    </div>
    <div class="offcanvas offcanvas-end" id="leaveDrawer" style="width:380px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Leave Request</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('admin.employees.leave.store', $employee) }}" class="offcanvas-body d-flex flex-column gap-2">
            @csrf
            <label class="form-label">Type</label><select name="leave_type_id" class="form-select" required>@foreach ($leaveTypes as $t)<option value="{{ $t->id }}">{{ $t->name }} ({{ $t->default_days }}d)</option>@endforeach</select>
            <label class="form-label">Start</label><input type="date" name="start_date" class="form-control" required>
            <label class="form-label">End</label><input type="date" name="end_date" class="form-control" required>
            <label class="form-label">Reason</label><textarea name="reason" class="form-control" rows="2"></textarea>
            <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Submit</button></div>
        </form>
    </div>
</x-app-layout>
