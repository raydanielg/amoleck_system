<x-app-layout>
    <x-slot name="header">{{ __('Staff & Access Management') }}</x-slot>
    <x-slot name="headerActions"><button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#userDrawer"><i class="hgi-stroke hgi-plus-sign me-1"></i>New Staff Account</button></x-slot>
    <div class="card shadow-sm border-0">
        <div class="table-responsive"><table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Name</th><th>Email</th><th>Role</th><th>Employee</th><th>Status</th><th class="text-end">Permissions</th></tr></thead>
            <tbody>@foreach ($users as $u)
                <tr>
                    <td class="fw-semibold">{{ $u->name }}</td><td class="small">{{ $u->email }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.access.users.role', $u) }}" class="d-flex gap-1">@csrf @method('PUT')
                            <select name="role" class="form-select form-select-sm" style="width:150px">@foreach (['ADMIN','RECEPTIONIST','CLINIC_STAFF'] as $r)<option @selected($u->role===$r)>{{ $r }}</option>@endforeach</select>
                            <button class="btn btn-sm btn-outline-secondary">Set</button>
                        </form>
                    </td>
                    <td class="small text-muted">{{ $u->employee?->employee_number ?? '—' }}</td>
                    <td><span class="badge {{ $u->status==='active'?'text-bg-success':'text-bg-secondary' }}">{{ $u->status }}</span></td>
                    <td class="text-end"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="offcanvas" data-bs-target="#perm{{ $u->id }}">Manage</button></td>
                </tr>
            @endforeach</tbody>
        </table></div>
    </div>
    @foreach ($users as $u)
        <div class="offcanvas offcanvas-end" id="perm{{ $u->id }}" style="width:460px">
            <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">{{ $u->name }} — {{ $u->role }}</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
            <div class="offcanvas-body">
                <p class="small text-muted">Overrides apply on top of the <strong>{{ $u->role }}</strong> role defaults. Green = granted, grey = denied.</p>
                @foreach ($groups as $group => $perms)
                    <div class="fw-semibold small text-muted mt-3 mb-1">{{ strtoupper($group) }}</div>
                    @foreach ($perms as $perm)
                        @php $on = $u->hasPermission($perm['key']); $ov = $u->permissionOverrides->firstWhere('permission', $perm['key']); @endphp
                        <div class="d-flex justify-content-between align-items-center border rounded px-2 py-1 mb-1">
                            <div class="small">{{ $perm['description'] }} <span class="text-muted">{{ $ov ? '(override)' : '' }}</span></div>
                            <form method="POST" action="{{ route('admin.access.users.permission', $u) }}">@csrf
                                <input type="hidden" name="permission" value="{{ $perm['key'] }}"><input type="hidden" name="allowed" value="{{ $on ? 0 : 1 }}">
                                <button class="btn btn-sm {{ $on ? 'btn-success' : 'btn-outline-secondary' }}" {{ $u->isAdmin() ? 'disabled' : '' }}>{{ $on ? 'On' : 'Off' }}</button>
                            </form>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endforeach
    <div class="offcanvas offcanvas-end" id="userDrawer" style="width:460px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">New Staff Account</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('admin.access.users.store') }}" class="offcanvas-body d-flex flex-column row g-2">
            @csrf
            <div class="col-12"><label class="form-label">Full Name *</label><input name="name" class="form-control" required></div>
            <div class="col-12"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
            <div class="col-12"><label class="form-label">Password * <span class="text-muted small">(user must change at first login)</span></label><input type="password" name="password" class="form-control" required></div>
            <div class="col-12"><label class="form-label">Role *</label><select name="role" class="form-select"><option value="RECEPTIONIST">Receptionist</option><option value="CLINIC_STAFF">Clinic Staff</option><option value="ADMIN">Admin</option></select></div>
            <div class="col-6"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
            <div class="col-6"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">—</option>@foreach (\App\Models\Department::where('is_active',true)->get() as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
            <div class="col-6"><label class="form-label">Position</label><select name="job_position_id" class="form-select"><option value="">—</option>@foreach (\App\Models\JobPosition::where('is_active',true)->get() as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach</select></div>
            <div class="col-6"><label class="form-label">Branch</label><select name="branch_id" class="form-select"><option value="">—</option>@foreach (\App\Models\Branch::where('status','active')->get() as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
            <div class="col-12 mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Create Account</button></div>
        </form>
    </div>
</x-app-layout>
