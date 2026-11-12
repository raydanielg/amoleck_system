<x-app-layout>
    <x-slot name="header">{{ __('Stock Movements') }}</x-slot>
    <x-slot name="headerActions"><a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary btn-sm">Back to Inventory</a></x-slot>
    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2">
                <div class="col-md-5"><select name="product" class="form-select"><option value="">All products</option>@foreach ($products as $p)<option value="{{ $p->id }}" @selected(request('product') == $p->id)>{{ $p->name }} ({{ $p->sku }})</option>@endforeach</select></div>
                <div class="col-md-4"><select name="type" class="form-select"><option value="">All types</option>@foreach (['received','adjusted','sold','returned','purchase_received'] as $t)<option @selected(request('type') === $t)>{{ $t }}</option>@endforeach</select></div>
                <div class="col-md-3"><button class="btn btn-outline-secondary w-100">Filter</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Date</th><th>Product</th><th>Type</th><th class="text-end">Qty</th><th class="text-end">After</th><th>Reference</th><th>Reason</th><th>By</th></tr></thead>
                <tbody>
                    @forelse ($movements as $m)
                        <tr><td class="text-muted">{{ $m->created_at->format('d M Y H:i') }}</td><td>{{ $m->product?->name }}</td><td><span class="badge text-bg-light border">{{ $m->type }}</span></td><td class="text-end {{ $m->quantity > 0 ? 'text-success' : 'text-danger' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td><td class="text-end">{{ $m->stock_after }}</td><td class="text-muted">{{ $m->reference ?? '—' }}</td><td class="text-muted">{{ $m->reason ?? '—' }}</td><td class="text-muted">{{ $m->user?->name }}</td></tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No movements.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($movements->hasPages())<div class="card-footer bg-white">{{ $movements->links() }}</div>@endif
    </div>
</x-app-layout>
