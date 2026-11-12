<x-app-layout>
    <x-slot name="header">{{ __('My Workplace') }}</x-slot>
    @if (! $employee)
        <div class="alert alert-info">Your account is not linked to an employee record. Ask your administrator to link one.</div>
    @else
    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="stat-card"><div class="stat-label">{{ $employee->employee_number }}</div><div class="fw-semibold">{{ $employee->full_name }}</div><div class="small text-muted">{{ $employee->jobPosition?->title }} · {{ $employee->department?->name }} · {{ $employee->branch?->name }}</div></div></div>
        <div class="col-md-4"><div class="stat-card"><div class="stat-label">Today</div>
            @if (! $today || ! $today->check_in)<div class="small text-muted">Not clocked in</div>
            @else<div class="fw-semibold">In {{ $today->check_in }}{{ $today->check_out ? ' · Out '.$today->check_out : '' }}</div>
            <span class="badge {{ $today->status==='late'?'text-bg-warning':'text-bg-success' }}">{{ $today->status }}</span>@endif
        </div></div>
        <div class="col-md-4"><div class="stat-card d-flex gap-2 align-items-center justify-content-center">
            @can('self.attendance')
            @endcan
            @if (auth()->user()->hasPermission('self.attendance'))
                @if (! $today || ! $today->check_in)<form method="POST" action="{{ route('my.clockin') }}">@csrf<button class="btn btn-success">Clock In</button></form>
                @elseif (! $today->check_out)<form method="POST" action="{{ route('my.clockout') }}">@csrf<button class="btn btn-warning">Clock Out</button></form>
                @else<span class="badge text-bg-secondary">Day complete</span>@endif
            @endif
        </div></div>
    </div>
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-3"><div class="card-header bg-white fw-semibold d-flex justify-content-between">My Leave
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="offcanvas" data-bs-target="#leaveDrawer">Apply</button></div>
                <ul class="list-group list-group-flush small">
                    @forelse ($leaveRequests as $lr)<li class="list-group-item d-flex justify-content-between"><span>{{ $lr->leaveType->name }} · {{ $lr->days }}d</span><span class="badge {{ ['approved'=>'text-bg-success','rejected'=>'text-bg-danger'][$lr->status] ?? 'text-bg-warning' }}">{{ $lr->status }}</span></li>
                    @empty<li class="list-group-item text-muted">No requests.</li>@endforelse
                </ul>
            </div>
            <div class="card shadow-sm border-0 mb-3"><div class="card-header bg-white fw-semibold d-flex justify-content-between">My Requests
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="offcanvas" data-bs-target="#reqDrawer">New</button></div>
                <ul class="list-group list-group-flush small">
                    @forelse ($requests as $r)<li class="list-group-item d-flex justify-content-between"><span>{{ $r->subject }}</span><span class="badge text-bg-light border">{{ $r->status }}</span></li>
                    @empty<li class="list-group-item text-muted">None.</li>@endforelse
                </ul>
            </div>
            <div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">My Assets</div>
                <ul class="list-group list-group-flush small">
                    @forelse ($employee->assets as $a)<li class="list-group-item d-flex justify-content-between"><span>{{ $a->name }}</span><span class="text-muted">{{ $a->asset_number }}</span></li>
                    @empty<li class="list-group-item text-muted">None assigned.</li>@endforelse
                </ul>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">Attendance History</div>
                <div class="table-responsive"><table class="table table-sm mb-0"><thead class="table-light"><tr><th>Date</th><th>In</th><th>Out</th><th>Status</th></tr></thead>
                <tbody>@forelse ($attendance as $a)<tr><td>{{ $a->date->format('d M') }}</td><td>{{ $a->check_in ?? '—' }}</td><td>{{ $a->check_out ?? '—' }}</td><td><span class="badge {{ $a->status==='late'?'text-bg-warning':($a->status==='absent'?'text-bg-danger':'text-bg-light border') }}">{{ $a->status }}</span></td></tr>
                @empty<tr><td colspan="4" class="text-center text-muted py-3">No records.</td></tr>@endforelse</tbody></table></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0"><div class="card-header bg-white fw-semibold">My Payslips</div>
                <ul class="list-group list-group-flush small">
                    @if (auth()->user()->hasPermission('self.payslip'))
                        @forelse ($payslips as $p)<li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ \Carbon\Carbon::create($p->batch->year,$p->batch->month)->format('M Y') }}<div class="text-muted">Net {{ number_format((float) $p->net) }}</div></span>
                            <a href="{{ route('my.payslip', $p) }}" class="btn btn-sm btn-outline-primary" target="_blank">PDF</a></li>
                        @empty<li class="list-group-item text-muted">No payslips released yet.</li>@endforelse
                    @else<li class="list-group-item text-muted">Payslip access is not enabled for your account.</li>@endif
                </ul>
            </div>
        </div>
    </div>
    <div class="offcanvas offcanvas-end" id="leaveDrawer" style="width:380px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Apply for Leave</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('my.leave') }}" class="offcanvas-body d-flex flex-column gap-2">@csrf
            <select name="leave_type_id" class="form-select" required>@foreach ($leaveTypes as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
            <input type="date" name="start_date" class="form-control" required><input type="date" name="end_date" class="form-control" required>
            <textarea name="reason" class="form-control" rows="3" placeholder="Reason"></textarea>
            <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Submit</button></div>
        </form>
    </div>
    <div class="offcanvas offcanvas-end" id="reqDrawer" style="width:380px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">New HR Request</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('my.requests') }}" class="offcanvas-body d-flex flex-column gap-2">@csrf
            <select name="type" class="form-select"><option value="info_update">Personal info update</option><option value="attendance_correction">Attendance correction</option><option value="document">Document request</option><option value="other">Other</option></select>
            <input name="subject" class="form-control" placeholder="Subject" required>
            <textarea name="details" class="form-control" rows="4" placeholder="Details"></textarea>
            <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Submit</button></div>
        </form>
    </div>
    @endif
</x-app-layout>
