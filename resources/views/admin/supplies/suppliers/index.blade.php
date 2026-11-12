<x-app-layout>
    <x-slot name="header">{{ __('Suppliers') }}</x-slot>
    <x-slot name="headerActions"><button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#supDrawer"><i class="hgi-stroke hgi-plus-sign me-1"></i>Add Supplier</button></x-slot>
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>Name</th><th>Contact</th><th>Phone</th><th>Email</th><th>Purchases</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($suppliers as $s)
                        <tr>
                            <td class="fw-semibold">{{ $s->name }}<div class="small text-muted fw-normal">{{ $s->company }}</div></td>
                            <td>{{ $s->contact_person ?? '—' }}</td>
                            <td>{{ $s->phone ?? '—' }}</td>
                            <td>{{ $s->email ?? '—' }}</td>
                            <td>{{ $s->purchases_count }}</td>
                            <td><span class="badge {{ $s->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $s->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="offcanvas" data-bs-target="#supEdit{{ $s->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.suppliers.toggle', $s) }}" class="d-inline">@csrf<button class="btn btn-sm {{ $s->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $s->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No suppliers.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($suppliers->hasPages())<div class="card-footer bg-white">{{ $suppliers->links() }}</div>@endif
    </div>
    @php $supplier = null; @endphp
    <div class="offcanvas offcanvas-end" tabindex="-1" id="supDrawer" style="width:440px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Add Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('admin.suppliers.store') }}" class="offcanvas-body d-flex flex-column">
            @csrf @include('admin.supplies.suppliers._form')
            <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Create</button></div>
        </form>
    </div>
    @foreach ($suppliers as $s)
        <div class="offcanvas offcanvas-end" tabindex="-1" id="supEdit{{ $s->id }}" style="width:440px">
            <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Edit Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
            <form method="POST" action="{{ route('admin.suppliers.update', $s) }}" class="offcanvas-body d-flex flex-column">
                @csrf @method('PUT') @include('admin.supplies.suppliers._form', ['supplier' => $s])
                <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Save</button></div>
            </form>
        </div>
    @endforeach
</x-app-layout>
