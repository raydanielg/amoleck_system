@php $cur = \App\Support\Settings::get('currency', 'TZS'); @endphp
<x-app-layout>
    <x-slot name="header">{{ __('Purchase '.$purchase->purchase_reference) }}</x-slot>
    <x-slot name="headerActions"><a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-secondary btn-sm">All Purchases</a></x-slot>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body border-bottom py-2 fw-semibold">Items</div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light"><tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Unit cost</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @foreach ($purchase->items as $item)
                                <tr><td>{{ $item->product?->name }}<div class="small text-muted">{{ $item->product?->sku }}</div></td><td class="text-center">{{ $item->quantity }}</td><td class="text-end">{{ number_format((float) $item->unit_cost, 2) }}</td><td class="text-end">{{ number_format((float) $item->line_total, 2) }}</td></tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end">{{ $cur }} {{ number_format((float) $purchase->total, 2) }}</td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <dl class="row mb-3 small">
                        <dt class="col-5 text-muted">Status</dt><dd class="col-7"><span class="badge {{ ['draft'=>'text-bg-warning','received'=>'text-bg-success','cancelled'=>'text-bg-secondary'][$purchase->status] ?? 'text-bg-light' }}">{{ $purchase->status }}</span></dd>
                        <dt class="col-5 text-muted">Supplier</dt><dd class="col-7">{{ $purchase->supplier?->name ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Created by</dt><dd class="col-7">{{ $purchase->creator?->name }}</dd>
                        <dt class="col-5 text-muted">Date</dt><dd class="col-7">{{ $purchase->created_at->format('d M Y H:i') }}</dd>
                        @if ($purchase->received_at)<dt class="col-5 text-muted">Received</dt><dd class="col-7">{{ $purchase->received_at->format('d M Y H:i') }}</dd>@endif
                        @if ($purchase->notes)<dt class="col-5 text-muted">Notes</dt><dd class="col-7">{{ $purchase->notes }}</dd>@endif
                    </dl>
                    @if ($purchase->status === 'draft')
                        <form method="POST" action="{{ route('admin.purchases.receive', $purchase) }}" class="d-grid gap-2" onsubmit="return confirm('Receive all items into stock?')">
                            @csrf<button class="btn btn-success"><i class="hgi-stroke hgi-checkmark-circle-02 me-1"></i>Receive Goods into Stock</button>
                        </form>
                        <form method="POST" action="{{ route('admin.purchases.cancel', $purchase) }}" class="d-grid mt-2" onsubmit="return confirm('Cancel this purchase?')">
                            @csrf<button class="btn btn-outline-danger">Cancel Purchase</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
