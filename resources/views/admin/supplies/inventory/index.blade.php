<x-app-layout>
    <x-slot name="header">{{ __('Inventory') }}</x-slot>
    <x-slot name="headerActions"><a href="{{ route('admin.inventory.movements') }}" class="btn btn-outline-secondary btn-sm">Movement History</a></x-slot>
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body py-3"><div class="small text-muted">Stock value (cost)</div><div class="fw-bold fs-5">{{ number_format((float) $valuation->cost_value, 2) }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body py-3"><div class="small text-muted">Stock value (retail)</div><div class="fw-bold fs-5">{{ number_format((float) $valuation->retail_value, 2) }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body py-3"><div class="small text-muted">Low stock</div><div class="fw-bold fs-5 text-warning">{{ $lowStock }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body py-3"><div class="small text-muted">Out of stock</div><div class="fw-bold fs-5 text-danger">{{ $outOfStock }}</div></div></div></div>
    </div>
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body border-bottom py-2 fw-semibold">Stock Levels</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>SKU</th><th>Product</th><th>Category</th><th class="text-end">On hand</th><th class="text-end">Min</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($products as $p)
                        <tr>
                            <td class="text-muted small">{{ $p->sku }}</td>
                            <td class="fw-semibold">{{ $p->name }}</td>
                            <td>{{ $p->category?->name ?? '—' }}</td>
                            <td class="text-end fw-semibold">{{ $p->stock_quantity }} <span class="small text-muted">{{ $p->unit }}</span></td>
                            <td class="text-end text-muted">{{ $p->min_stock_level }}</td>
                            <td>
                                @if ($p->isOutOfStock())<span class="badge text-bg-danger">Out of stock</span>
                                @elseif ($p->isLowStock())<span class="badge text-bg-warning">Low</span>
                                @else<span class="badge text-bg-success">OK</span>@endif
                            </td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-success" data-bs-toggle="offcanvas" data-bs-target="#receive{{ $p->id }}">Receive</button>
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="offcanvas" data-bs-target="#adjust{{ $p->id }}">Adjust</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No products.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())<div class="card-footer bg-white">{{ $products->links() }}</div>@endif
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom py-2 fw-semibold">Recent Movements</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Date</th><th>Product</th><th>Type</th><th class="text-end">Qty</th><th class="text-end">After</th><th>Reference</th><th>By</th></tr></thead>
                <tbody>
                    @forelse ($movements as $m)
                        <tr><td class="text-muted">{{ $m->created_at->format('d M H:i') }}</td><td>{{ $m->product?->name }}</td><td><span class="badge text-bg-light border">{{ $m->type }}</span></td><td class="text-end {{ $m->quantity > 0 ? 'text-success' : 'text-danger' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td><td class="text-end">{{ $m->stock_after }}</td><td class="text-muted">{{ $m->reference ?? '—' }}</td><td class="text-muted">{{ $m->user?->name }}</td></tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">No movements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @foreach ($products as $p)
        <div class="offcanvas offcanvas-end" tabindex="-1" id="receive{{ $p->id }}" style="width:420px">
            <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Receive — {{ $p->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
            <form method="POST" action="{{ route('admin.inventory.receive', $p) }}" class="offcanvas-body d-flex flex-column">
                @csrf
                <label class="form-label">Quantity *</label><input type="number" min="1" name="quantity" class="form-control mb-2" required>
                <label class="form-label">Unit cost (updates purchase price)</label><input type="number" step="0.01" min="0" name="unit_cost" class="form-control mb-2" value="{{ $p->purchase_price }}">
                <label class="form-label">Reference</label><input name="reference" class="form-control mb-2" placeholder="GRN, delivery note...">
                <label class="form-label">Reason</label><input name="reason" class="form-control" value="Stock received">
                <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Receive Stock</button></div>
            </form>
        </div>
        <div class="offcanvas offcanvas-end" tabindex="-1" id="adjust{{ $p->id }}" style="width:420px">
            <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Adjust — {{ $p->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
            <form method="POST" action="{{ route('admin.inventory.adjust', $p) }}" class="offcanvas-body d-flex flex-column">
                @csrf
                <p class="small text-muted">Current stock: <strong>{{ $p->stock_quantity }} {{ $p->unit }}</strong>. Use negative to reduce.</p>
                <label class="form-label">Adjustment (+/-) *</label><input type="number" name="quantity" class="form-control mb-2" required>
                <label class="form-label">Reason *</label><input name="reason" class="form-control" required placeholder="Damaged, counted, correction...">
                <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Apply Adjustment</button></div>
            </form>
        </div>
    @endforeach
</x-app-layout>
