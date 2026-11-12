<x-app-layout>
    <x-slot name="header">{{ __('Message Templates') }}</x-slot>
    <x-slot name="headerActions"><button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#tplDrawer"><i class="hgi-stroke hgi-plus-sign me-1"></i>New Template</button></x-slot>
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>Key</th><th>Name</th><th>Category</th><th>Body</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($templates as $t)
                        <tr>
                            <td class="small text-muted">{{ $t->key }}</td>
                            <td class="fw-semibold">{{ $t->name }}</td>
                            <td><span class="badge text-bg-light border">{{ $t->category }}</span></td>
                            <td><div class="small text-muted text-truncate" style="max-width:320px">{{ $t->body }}</div></td>
                            <td><span class="badge {{ $t->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $t->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="offcanvas" data-bs-target="#tplEdit{{ $t->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.communication.templates.toggle', $t) }}" class="d-inline">@csrf<button class="btn btn-sm {{ $t->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $t->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No templates.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($templates->hasPages())<div class="card-footer bg-white">{{ $templates->links() }}</div>@endif
    </div>
    <div class="offcanvas offcanvas-end" tabindex="-1" id="tplDrawer" style="width:460px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">New Template</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('admin.communication.templates.store') }}" class="offcanvas-body d-flex flex-column">
            @csrf
            <label class="form-label">Key *</label><input name="key" class="form-control mb-2" placeholder="payment.received" required>
            <label class="form-label">Name *</label><input name="name" class="form-control mb-2" required>
            <label class="form-label">Category *</label><input name="category" class="form-control mb-2" list="cats" required><datalist id="cats"><option value="patient"><option value="billing"><option value="orders"><option value="general"></datalist>
            <label class="form-label">Body * <span class="text-muted small">(use &#123;&#123;variable&#125;&#125; placeholders)</span></label><textarea name="body" class="form-control" rows="5" required></textarea>
            <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Create</button></div>
        </form>
    </div>
    @foreach ($templates as $t)
        <div class="offcanvas offcanvas-end" tabindex="-1" id="tplEdit{{ $t->id }}" style="width:460px">
            <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Edit — {{ $t->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
            <form method="POST" action="{{ route('admin.communication.templates.update', $t) }}" class="offcanvas-body d-flex flex-column">
                @csrf @method('PUT')
                <label class="form-label">Name *</label><input name="name" class="form-control mb-2" required value="{{ $t->name }}">
                <label class="form-label">Category *</label><input name="category" class="form-control mb-2" required value="{{ $t->category }}" list="cats">
                <label class="form-label">Body *</label><textarea name="body" class="form-control" rows="6" required>{{ $t->body }}</textarea>
                <div class="small text-muted mt-1">Variables: {{ implode(', ', $t->placeholders()) ?: 'none' }}</div>
                <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Save</button></div>
            </form>
        </div>
    @endforeach
</x-app-layout>
