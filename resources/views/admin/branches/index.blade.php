<x-app-layout>
    <x-slot name="header">{{ __('Branches') }}</x-slot>
    <x-slot name="headerActions"><button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#brDrawer"><i class="hgi-stroke hgi-plus-sign me-1"></i>New Branch</button></x-slot>
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Consolidated Revenue</div><div class="stat-value">{{ number_format($consolidated['revenue']) }}</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Total Expenses</div><div class="stat-value text-danger">{{ number_format($consolidated['expenses']) }}</div></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Patients</div><div class="stat-value">{{ $consolidated['patients'] }}</div></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Employees</div><div class="stat-value">{{ $consolidated['employees'] }}</div></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Assets</div><div class="stat-value">{{ $consolidated['assets'] }}</div></div></div>
    </div>
    <div class="card shadow-sm border-0">
        <div class="table-responsive"><table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Code</th><th>Name</th><th>Type</th><th>Location</th><th>Manager</th><th>Staff</th><th>Status</th><th></th></tr></thead>
            <tbody>@forelse ($branches as $b)
                <tr><td class="small text-muted">{{ $b->code }}</td><td class="fw-semibold">{{ $b->name }}</td><td><span class="badge text-bg-light border">{{ $b->type }}</span></td>
                <td>{{ $b->district }} {{ $b->region }}</td><td>{{ $b->manager ?? '—' }}</td><td>{{ $b->employees_count }}</td>
                <td><span class="badge {{ $b->status==='active'?'text-bg-success':'text-bg-secondary' }}">{{ $b->status }}</span></td>
                <td class="text-end"><a href="{{ route('admin.branches.show', $b) }}" class="btn btn-sm btn-outline-primary">View</a></td></tr>
            @empty<tr><td colspan="8" class="text-center text-muted py-4">No branches yet.</td></tr>@endforelse</tbody>
        </table></div>
    </div>
    <div class="offcanvas offcanvas-end" id="brDrawer" style="width:440px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">New Branch</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('admin.branches.store') }}" class="offcanvas-body d-flex flex-column row g-2">
            @csrf
            <div class="col-12"><label class="form-label">Name *</label><input name="name" class="form-control" required></div>
            <div class="col-6"><label class="form-label">Type *</label><select name="type" class="form-select"><option value="clinic">Physiotherapy Clinic</option><option value="store">Medical Equipment Store</option><option value="office">Main Office</option><option value="warehouse">Warehouse</option><option value="other">Other</option></select></div>
            <div class="col-6"><label class="form-label">Manager</label><input name="manager" class="form-control"></div>
            <div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control"></div>
            <div class="col-6"><label class="form-label">Region</label><input name="region" class="form-control"></div>
            <div class="col-6"><label class="form-label">District</label><input name="district" class="form-control"></div>
            <div class="col-6"><label class="form-label">Phone</label><input name="phone" class="form-control"></div>
            <div class="col-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
            <div class="col-6"><label class="form-label">Opening Date</label><input type="date" name="opened_at" class="form-control"></div>
            <div class="col-12 mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Create Branch</button></div>
        </form>
    </div>
</x-app-layout>
