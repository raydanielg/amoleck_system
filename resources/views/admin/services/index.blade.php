<x-app-layout>
    <x-slot name="header">{{ __('Services') }}</x-slot>
    <x-slot name="headerActions">
        <div class="d-flex gap-2">
            <a href="{{ route('admin.service-categories.index') }}" class="btn btn-outline-secondary btn-sm">Categories</a>
            <a href="{{ route('admin.services.create') }}" class="btn btn-primary btn-sm"><i class="hgi-stroke hgi-plus-sign me-1"></i>Add Service</a>
        </div>
    </x-slot>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2">
                <div class="col-md-5"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search service..."></div>
                <div class="col-md-4">
                    <select name="category" class="form-select">
                        <option value="">All categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>#</th><th>Code</th><th>Name</th><th>Category</th><th class="text-end">Price</th><th>Duration</th><th>Notes Req.</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        <tr>
                            <td class="text-muted">{{ $service->sort_order }}</td>
                            <td>{{ $service->service_code ?? '—' }}</td>
                            <td class="fw-semibold"><a href="{{ route('admin.services.show', $service) }}">{{ $service->name }}</a></td>
                            <td>{{ $service->category?->name ?? '—' }}</td>
                            <td class="text-end">{{ $service->standard_price !== null ? number_format((float) $service->standard_price) : '—' }}</td>
                            <td>{{ $service->duration_minutes ? $service->duration_minutes.' min' : '—' }}</td>
                            <td>{{ $service->requires_notes ? 'Yes' : 'No' }}</td>
                            <td><span class="badge {{ $service->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $service->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.services.edit', $service) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.services.toggle', $service) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm {{ $service->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                        {{ $service->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No services configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($services->hasPages())<div class="card-footer bg-white">{{ $services->links() }}</div>@endif
    </div>
</x-app-layout>
