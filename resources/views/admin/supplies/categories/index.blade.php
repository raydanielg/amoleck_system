<x-app-layout>
    <x-slot name="header">{{ __('Product Categories') }}</x-slot>
    <x-slot name="headerActions">
        <button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#catDrawer"><i class="hgi-stroke hgi-plus-sign me-1"></i>Add Category</button>
    </x-slot>
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>Name</th><th>Description</th><th>Products</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($categories as $c)
                        <tr>
                            <td class="fw-semibold">{{ $c->name }}</td>
                            <td class="text-muted">{{ $c->description ?? '—' }}</td>
                            <td>{{ $c->products_count }}</td>
                            <td><span class="badge {{ $c->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $c->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="offcanvas" data-bs-target="#catEdit{{ $c->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.product-categories.toggle', $c) }}" class="d-inline">@csrf<button class="btn btn-sm {{ $c->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $c->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No categories.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($categories->hasPages())<div class="card-footer bg-white">{{ $categories->links() }}</div>@endif
    </div>
    <div class="offcanvas offcanvas-end" tabindex="-1" id="catDrawer" style="width:420px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Add Category</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('admin.product-categories.store') }}" class="offcanvas-body d-flex flex-column">
            @csrf
            <label class="form-label">Name *</label><input name="name" class="form-control mb-2" required>
            <label class="form-label">Description</label><textarea name="description" class="form-control mb-2" rows="3"></textarea>
            <label class="form-label">Sort order</label><input type="number" min="0" name="sort_order" class="form-control" value="0">
            <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Create</button></div>
        </form>
    </div>
    @foreach ($categories as $c)
        <div class="offcanvas offcanvas-end" tabindex="-1" id="catEdit{{ $c->id }}" style="width:420px">
            <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Edit Category</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
            <form method="POST" action="{{ route('admin.product-categories.update', $c) }}" class="offcanvas-body d-flex flex-column">
                @csrf @method('PUT')
                <label class="form-label">Name *</label><input name="name" class="form-control mb-2" required value="{{ $c->name }}">
                <label class="form-label">Description</label><textarea name="description" class="form-control mb-2" rows="3">{{ $c->description }}</textarea>
                <label class="form-label">Sort order</label><input type="number" min="0" name="sort_order" class="form-control" value="{{ $c->sort_order }}">
                <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Save</button></div>
            </form>
        </div>
    @endforeach
</x-app-layout>
