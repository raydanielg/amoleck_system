<x-app-layout>
    <x-slot name="header">{{ __('Purchases') }}</x-slot>
    <x-slot name="headerActions"><a href="{{ route('admin.purchases.create') }}" class="btn btn-primary btn-sm"><i class="hgi-stroke hgi-plus-sign me-1"></i>New Purchase</a></x-slot>
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>Reference</th><th>Supplier</th><th>Items</th><th class="text-end">Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
                <tbody>
                    @forelse ($purchases as $p)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.purchases.show', $p) }}">{{ $p->purchase_reference }}</a></td>
                            <td>{{ $p->supplier?->name ?? '—' }}</td>
                            <td>{{ $p->items_count }}</td>
                            <td class="text-end">{{ number_format((float) $p->total, 2) }}</td>
                            <td><span class="badge {{ ['draft'=>'text-bg-warning','received'=>'text-bg-success','cancelled'=>'text-bg-secondary'][$p->status] ?? 'text-bg-light' }}">{{ $p->status }}</span></td>
                            <td class="text-muted">{{ $p->created_at->format('d M Y') }}</td>
                            <td class="text-end"><a href="{{ route('admin.purchases.show', $p) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No purchases.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($purchases->hasPages())<div class="card-footer bg-white">{{ $purchases->links() }}</div>@endif
    </div>
</x-app-layout>
