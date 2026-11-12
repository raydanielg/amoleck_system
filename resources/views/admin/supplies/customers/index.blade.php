<x-app-layout>
    <x-slot name="header">{{ __('Customers') }}</x-slot>
    <x-slot name="headerActions"><button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#custDrawer"><i class="hgi-stroke hgi-plus-sign me-1"></i>Add Customer</button></x-slot>
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>Name</th><th>Phone</th><th>Email</th><th>Address</th><th>Sales</th><th></th></tr></thead>
                <tbody>
                    @forelse ($customers as $c)
                        <tr>
                            <td class="fw-semibold">{{ $c->name }}</td>
                            <td>{{ $c->phone ?? '—' }}</td>
                            <td>{{ $c->email ?? '—' }}</td>
                            <td>{{ $c->address ?? '—' }}</td>
                            <td>{{ $c->sales_count }}</td>
                            <td class="text-end"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="offcanvas" data-bs-target="#custEdit{{ $c->id }}">Edit</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No customers.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($customers->hasPages())<div class="card-footer bg-white">{{ $customers->links() }}</div>@endif
    </div>
    <div class="offcanvas offcanvas-end" tabindex="-1" id="custDrawer" style="width:400px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Add Customer</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('admin.customers.store') }}" class="offcanvas-body d-flex flex-column">
            @csrf @include('admin.supplies.customers._form')
            <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Create</button></div>
        </form>
    </div>
    @foreach ($customers as $c)
        <div class="offcanvas offcanvas-end" tabindex="-1" id="custEdit{{ $c->id }}" style="width:400px">
            <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Edit Customer</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
            <form method="POST" action="{{ route('admin.customers.update', $c) }}" class="offcanvas-body d-flex flex-column">
                @csrf @method('PUT') @include('admin.supplies.customers._form', ['customer' => $c])
                <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Save</button></div>
            </form>
        </div>
    @endforeach
</x-app-layout>
