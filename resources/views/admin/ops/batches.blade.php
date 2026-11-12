<x-app-layout>
    <x-slot name="header">{{ __('Inventory Batches') }}</x-slot>
    <x-slot name="headerActions"><button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#batchDrawer"><i class="hgi-stroke hgi-plus-sign me-1"></i>Receive Batch</button></x-slot>
    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="stat-card"><div class="stat-label">Expiring (warning window)</div><div class="stat-value text-warning">{{ $summary['expiring'] }}</div></div></div>
        <div class="col-md-4"><div class="stat-card"><div class="stat-label">Expired</div><div class="stat-value text-danger">{{ $summary['expired'] }}</div></div></div>
        <div class="col-md-4"><div class="stat-card"><div class="stat-label">Quarantined</div><div class="stat-value">{{ $summary['quarantined'] }}</div></div></div>
    </div>
    <div class="card shadow-sm border-0">
        <div class="table-responsive"><table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Batch</th><th>Product</th><th>Supplier</th><th>Received</th><th>Qty</th><th>Unit Cost</th><th>Expiry</th><th>Status</th></tr></thead>
            <tbody>@forelse ($batches as $b)
                <tr><td class="small text-muted">{{ $b->batch_number }}</td><td class="fw-semibold">{{ $b->product?->name }}</td><td>{{ $b->supplier?->name ?? '—' }}</td>
                <td class="small text-muted">{{ $b->received_at?->format('d M Y') }}</td><td>{{ $b->qty_available }}/{{ $b->qty_received }}</td>
                <td>{{ $b->unit_cost ? number_format((float) $b->unit_cost) : '—' }}</td>
                <td>@if ($b->expires_at)<span class="badge {{ $b->expires_at->isPast()?'text-bg-danger':($b->expires_at->lte(now()->addDays(30))?'text-bg-warning':'text-bg-light border') }}">{{ $b->expires_at->format('d M Y') }}</span>@else — @endif</td>
                <td><span class="badge {{ ['active'=>'text-bg-success','expired'=>'text-bg-danger','quarantined'=>'text-bg-warning','depleted'=>'text-bg-secondary'][$b->status] }}">{{ $b->status }}</span></td></tr>
            @empty<tr><td colspan="8" class="text-center text-muted py-4">No batches recorded.</td></tr>@endforelse</tbody>
        </table></div>
        @if ($batches->hasPages())<div class="card-footer bg-white">{{ $batches->links() }}</div>@endif
    </div>
    <div class="offcanvas offcanvas-end" id="batchDrawer" style="width:440px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Receive Stock Batch</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('admin.batches.store') }}" class="offcanvas-body d-flex flex-column row g-2">
            @csrf
            <div class="col-12"><label class="form-label">Product *</label><select name="product_id" class="form-select" required>@foreach ($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
            <div class="col-6"><label class="form-label">Batch #</label><input name="batch_number" class="form-control" placeholder="Auto if blank"></div>
            <div class="col-6"><label class="form-label">Supplier</label><select name="supplier_id" class="form-select"><option value="">—</option>@foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
            <div class="col-6"><label class="form-label">Qty *</label><input type="number" name="qty" class="form-control" min="1" required></div>
            <div class="col-6"><label class="form-label">Unit Cost</label><input type="number" step="0.01" name="unit_cost" class="form-control"></div>
            <div class="col-6"><label class="form-label">Expiry Date</label><input type="date" name="expires_at" class="form-control"></div>
            <div class="col-6"><label class="form-label">Mfg Date</label><input type="date" name="manufactured_at" class="form-control"></div>
            <div class="col-12"><label class="form-label">Location</label><input name="location" class="form-control"></div>
            <div class="col-12 mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Receive</button></div>
        </form>
    </div>
</x-app-layout>
