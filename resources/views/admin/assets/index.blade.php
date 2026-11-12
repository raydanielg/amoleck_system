<x-app-layout>
    <x-slot name="header">{{ __('Asset Register') }}</x-slot>
    <x-slot name="headerActions">
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#catDrawer">Categories</button>
        <button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#assetDrawer"><i class="hgi-stroke hgi-plus-sign me-1"></i>Register Asset</button>
    </x-slot>
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Total Assets</div><div class="stat-value">{{ $stats['total'] }}</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Acquisition Value</div><div class="stat-value">{{ number_format((float) $stats['value']) }}</div></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Assigned</div><div class="stat-value">{{ $stats['assigned'] }}</div></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">In Maintenance</div><div class="stat-value">{{ $stats['maintenance'] }}</div></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Warranty Alerts</div><div class="stat-value text-warning">{{ $stats['warranty_expiring'] }}</div></div></div>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom"><form method="GET" class="row g-2">
            <div class="col-md-5"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Name, asset # or serial..."></div>
            <div class="col-md-3"><select name="category" class="form-select"><option value="">All categories</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category')==$c->id)>{{ $c->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><select name="status" class="form-select"><option value="">All statuses</option>@foreach (['active','maintenance','disposed'] as $st)<option @selected(request('status')===$st)>{{ $st }}</option>@endforeach</select></div>
            <div class="col-md-1"><button class="btn btn-outline-secondary w-100">Go</button></div>
        </form></div>
        <div class="table-responsive"><table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Asset #</th><th>Name</th><th>Category</th><th>Cost</th><th>Branch</th><th>Assigned To</th><th>Warranty</th><th>Status</th><th></th></tr></thead>
            <tbody>@forelse ($assets as $a)
                <tr><td class="small text-muted">{{ $a->asset_number }}</td><td class="fw-semibold">{{ $a->name }}<div class="small text-muted fw-normal">{{ $a->brand }} {{ $a->model }}</div></td>
                <td>{{ $a->category?->name ?? '—' }}</td><td>{{ $a->purchase_cost ? number_format((float) $a->purchase_cost) : '—' }}</td>
                <td>{{ $a->branch?->name ?? '—' }}</td><td>{{ $a->employee?->full_name ?? '—' }}</td>
                <td>@if ($a->warranty_expires)<span class="badge {{ $a->warranty_expires->isPast()?'text-bg-danger':($a->warranty_expires->lte(now()->addDays(60))?'text-bg-warning':'text-bg-success') }}">{{ $a->warranty_expires->format('d M Y') }}</span>@else — @endif</td>
                <td><span class="badge {{ ['active'=>'text-bg-success','maintenance'=>'text-bg-warning','disposed'=>'text-bg-dark'][$a->status] }}">{{ $a->status }}</span></td>
                <td class="text-end"><a href="{{ route('admin.assets.show', $a) }}" class="btn btn-sm btn-outline-primary">View</a></td></tr>
            @empty<tr><td colspan="9" class="text-center text-muted py-4">No assets registered.</td></tr>@endforelse</tbody>
        </table></div>
        @if ($assets->hasPages())<div class="card-footer bg-white">{{ $assets->links() }}</div>@endif
    </div>
    <div class="offcanvas offcanvas-end" id="assetDrawer" style="width:520px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Register Asset</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('admin.assets.store') }}" class="offcanvas-body d-flex flex-column row g-2">
            @csrf
            <div class="col-12"><label class="form-label">Name *</label><input name="name" class="form-control" required></div>
            <div class="col-6"><label class="form-label">Category</label><select name="asset_category_id" class="form-select"><option value="">—</option>@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
            <div class="col-6"><label class="form-label">Brand / Model</label><div class="d-flex gap-1"><input name="brand" class="form-control" placeholder="Brand"><input name="model" class="form-control" placeholder="Model"></div></div>
            <div class="col-6"><label class="form-label">Serial Number</label><input name="serial_number" class="form-control"></div>
            <div class="col-6"><label class="form-label">Supplier</label><input name="supplier_name" class="form-control"></div>
            <div class="col-6"><label class="form-label">Purchase Date</label><input type="date" name="purchased_at" class="form-control"></div>
            <div class="col-6"><label class="form-label">Purchase Cost</label><input type="number" step="0.01" name="purchase_cost" class="form-control"></div>
            <div class="col-6"><label class="form-label">Warranty Expiry</label><input type="date" name="warranty_expires" class="form-control"></div>
            <div class="col-6"><label class="form-label">Branch</label><select name="branch_id" class="form-select"><option value="">—</option>@foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
            <div class="col-4"><label class="form-label">Condition</label><select name="condition" class="form-select"><option value="new">New</option><option value="good" selected>Good</option><option value="fair">Fair</option><option value="poor">Poor</option></select></div>
            <div class="col-4"><label class="form-label">Useful Life (yrs)</label><input type="number" name="useful_life_years" class="form-control" min="1"></div>
            <div class="col-4"><label class="form-label">Residual Value</label><input type="number" step="0.01" name="residual_value" class="form-control" value="0"></div>
            <div class="col-6"><label class="form-label">Depreciation</label><select name="depreciation_method" class="form-select"><option value="straight_line">Straight line</option><option value="declining">Declining balance</option></select></div>
            <div class="col-12 mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Register</button></div>
        </form>
    </div>
    <div class="offcanvas offcanvas-end" id="catDrawer" style="width:360px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Asset Categories</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <div class="offcanvas-body">
            <form method="POST" action="{{ route('admin.asset-categories.store') }}" class="d-flex gap-2 mb-3">@csrf<input name="name" class="form-control" placeholder="New category" required><button class="btn btn-outline-primary">Add</button></form>
            <ul class="list-group">@foreach ($categories as $c)<li class="list-group-item">{{ $c->name }}</li>@endforeach</ul>
        </div>
    </div>
</x-app-layout>
