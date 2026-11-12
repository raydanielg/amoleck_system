<x-app-layout>
    <x-slot name="header">{{ __('Expense Categories') }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('admin.expense-categories.create') }}" class="btn btn-primary btn-sm"><i class="hgi-stroke hgi-plus-sign me-1"></i>Add Category</a>
    </x-slot>

    <div class="card shadow-sm border-0" style="max-width: 800px;">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>#</th><th>Name</th><th>Description</th><th>Expenses</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($categories as $cat)
                        <tr>
                            <td class="text-muted">{{ $cat->sort_order }}</td>
                            <td class="fw-semibold">{{ $cat->name }}</td>
                            <td class="small">{{ $cat->description ?? '—' }}</td>
                            <td>{{ $cat->expenses_count }}</td>
                            <td><span class="badge {{ $cat->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $cat->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.expense-categories.edit', $cat) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.expense-categories.toggle', $cat) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm {{ $cat->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $cat->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No categories.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
