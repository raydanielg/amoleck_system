<x-app-layout>
    <x-slot name="header">{{ __('Products') }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('admin.product-categories.index') }}" class="btn btn-outline-secondary btn-sm">Categories</a>
        <button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#productDrawer"><i class="hgi-stroke hgi-plus-sign me-1"></i>Add Product</button>
    </x-slot>
    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2">
                <div class="col-md-4"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name, SKU, barcode..."></div>
                <div class="col-md-3"><select name="category" class="form-select"><option value="">All categories</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><select name="stock" class="form-select"><option value="">All stock</option><option value="low" @selected(request('stock') === 'low')>Low stock</option><option value="out" @selected(request('stock') === 'out')>Out of stock</option></select></div>
                <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th></th><th>SKU</th><th>Name</th><th>Category</th><th class="text-end">Cost</th><th class="text-end">Price</th><th class="text-end">Stock</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($products as $p)
                        <tr>
                            <td style="width:44px">@if ($p->image_path)<img src="{{ $p->imageUrl() }}" class="rounded" style="width:36px;height:36px;object-fit:cover">@else<span class="badge text-bg-light border"><i class="hgi-stroke hgi-package"></i></span>@endif</td>
                            <td class="text-muted small">{{ $p->sku }}</td>
                            <td class="fw-semibold">{{ $p->name }}<div class="small text-muted fw-normal">{{ $p->supplier?->name }}</div></td>
                            <td>{{ $p->category?->name ?? '—' }}</td>
                            <td class="text-end">{{ number_format((float) $p->purchase_price, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $p->selling_price, 2) }}</td>
                            <td class="text-end">
                                @if ($p->isOutOfStock())<span class="badge text-bg-danger">{{ $p->stock_quantity }}</span>
                                @elseif ($p->isLowStock())<span class="badge text-bg-warning">{{ $p->stock_quantity }}</span>
                                @else{{ $p->stock_quantity }}@endif
                                <span class="small text-muted">{{ $p->unit }}</span>
                            </td>
                            <td><span class="badge {{ $p->isActive() ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $p->status }}</span></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="offcanvas" data-bs-target="#editDrawer{{ $p->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.products.toggle', $p) }}" class="d-inline">@csrf<button class="btn btn-sm {{ $p->isActive() ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $p->isActive() ? 'Deactivate' : 'Activate' }}</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No products yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())<div class="card-footer bg-white">{{ $products->links() }}</div>@endif
    </div>

    {{-- Add drawer --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="productDrawer" style="width:480px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Add Product</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="offcanvas-body d-flex flex-column">
            @csrf @include('admin.supplies.products._form')
            <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Create Product</button></div>
        </form>
    </div>
    @foreach ($products as $p)
        <div class="offcanvas offcanvas-end" tabindex="-1" id="editDrawer{{ $p->id }}" style="width:480px">
            <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Edit {{ $p->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
            <form method="POST" action="{{ route('admin.products.update', $p) }}" enctype="multipart/form-data" class="offcanvas-body d-flex flex-column">
                @csrf @method('PUT') @include('admin.supplies.products._form', ['product' => $p])
                <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Save Changes</button></div>
            </form>
        </div>
    @endforeach
</x-app-layout>
