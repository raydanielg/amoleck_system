@php $cur = \App\Support\Settings::get('currency', 'TZS'); @endphp
<x-app-layout>
    <x-slot name="header">{{ __('Sale '.$sale->sale_reference) }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('pos.receipt', $sale) }}" class="btn btn-outline-secondary btn-sm">View Receipt</a>
        <a href="{{ route('admin.sales.receipt-pdf', $sale) }}" class="btn btn-primary btn-sm"><i class="hgi-stroke hgi-download-01 me-1"></i>PDF</a>
    </x-slot>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body border-bottom py-2 fw-semibold">Items</div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light"><tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Cost</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @foreach ($sale->items as $item)
                                <tr><td>{{ $item->product_name }}<div class="small text-muted">{{ $item->sku }}</div></td><td class="text-center">{{ $item->quantity }}</td><td class="text-end">{{ number_format((float) $item->unit_price, 2) }}</td><td class="text-end text-muted">{{ number_format((float) $item->unit_cost, 2) }}</td><td class="text-end">{{ number_format((float) $item->line_total, 2) }}</td></tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr><td colspan="4" class="text-end text-muted">Subtotal</td><td class="text-end">{{ $cur }} {{ number_format((float) $sale->subtotal, 2) }}</td></tr>
                            @if ($sale->discount_amount > 0)<tr><td colspan="4" class="text-end text-muted">Discount ({{ $sale->discount_reason }})</td><td class="text-end text-danger">- {{ $cur }} {{ number_format((float) $sale->discount_amount, 2) }}</td></tr>@endif
                            @if ($sale->tax_amount > 0)<tr><td colspan="4" class="text-end text-muted">Tax ({{ $sale->tax_percent }}%)</td><td class="text-end">{{ $cur }} {{ number_format((float) $sale->tax_amount, 2) }}</td></tr>@endif
                            <tr class="fw-bold"><td colspan="4" class="text-end">Total</td><td class="text-end">{{ $cur }} {{ number_format((float) $sale->total, 2) }}</td></tr>
                            <tr><td colspan="4" class="text-end text-muted">Paid</td><td class="text-end">{{ $cur }} {{ number_format((float) $sale->amount_paid, 2) }}</td></tr>
                            @if ($sale->balance > 0)<tr class="text-danger"><td colspan="4" class="text-end">Balance</td><td class="text-end">{{ $cur }} {{ number_format((float) $sale->balance, 2) }}</td></tr>@endif
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card shadow-sm border-0">
                <div class="card-body border-bottom py-2 fw-semibold">Payments &amp; Refunds</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach ($sale->payments as $p)
                                <tr><td class="text-muted">{{ $p->payment_date->format('d M Y') }}</td><td>{{ $p->payment_method }}</td><td class="text-end text-success">+ {{ number_format((float) $p->amount, 2) }}</td><td class="text-muted">{{ $p->receiver?->name }}</td></tr>
                            @endforeach
                            @foreach ($sale->refunds as $r)
                                <tr><td class="text-muted">{{ $r->created_at->format('d M Y') }}</td><td>Refund {{ $r->refund_reference }} — {{ $r->reason }}</td><td class="text-end text-danger">- {{ number_format((float) $r->amount, 2) }}</td><td class="text-muted">{{ $r->refunder?->name }}</td></tr>
                            @endforeach
                            @if ($sale->payments->isEmpty() && $sale->refunds->isEmpty())<tr><td class="text-center text-muted py-3">No payments yet.</td></tr>@endif
                        </tbody>
                    </table>
                </div>
                @if ($sale->returns->isNotEmpty())
                    <div class="card-body border-top py-2 fw-semibold">Returns</div>
                    <div class="card-body py-2">
                        @foreach ($sale->returns as $ret)
                            <div class="small mb-1"><span class="badge text-bg-light border">{{ $ret->return_reference }}</span> {{ $ret->reason }} — @foreach ($ret->items as $ri){{ $ri->product?->name }} ×{{ $ri->quantity }}{{ $ri->restocked ? ' (restocked)' : '' }}{{ $loop->last ? '' : ', ' }}@endforeach</div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <dl class="row mb-3 small">
                        <dt class="col-5 text-muted">Status</dt><dd class="col-7"><span class="badge {{ ['paid'=>'text-bg-success','pending_payment'=>'text-bg-warning','partially_paid'=>'text-bg-info','partially_refunded'=>'text-bg-warning','refunded'=>'text-bg-secondary','cancelled'=>'text-bg-secondary'][$sale->status] ?? 'text-bg-light' }}">{{ str_replace('_', ' ', $sale->status) }}</span></dd>
                        <dt class="col-5 text-muted">Customer</dt><dd class="col-7">{{ $sale->customer?->name ?? $sale->customer_name ?? 'Walk-in' }}</dd>
                        <dt class="col-5 text-muted">Sold by</dt><dd class="col-7">{{ $sale->seller?->name }}</dd>
                        <dt class="col-5 text-muted">Date</dt><dd class="col-7">{{ $sale->created_at->format('d M Y H:i') }}</dd>
                        <dt class="col-5 text-muted">COGS</dt><dd class="col-7">{{ $cur }} {{ number_format((float) $sale->cogs(), 2) }}</dd>
                    </dl>
                    @if ($sale->balance > 0)
                        <button class="btn btn-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#payModal">Record Payment</button>
                    @endif
                    @if ($sale->amount_paid > 0 && ! in_array($sale->status, ['refunded', 'cancelled']))
                        <button class="btn btn-outline-danger w-100 mb-2" data-bs-toggle="modal" data-bs-target="#refundModal">Refund</button>
                    @endif
                    @if (! in_array($sale->status, ['cancelled', 'refunded']))
                        <button class="btn btn-outline-secondary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#returnModal">Record Return</button>
                    @endif
                    @if ($sale->amount_paid <= 0 && $sale->status !== 'cancelled')
                        <form method="POST" action="{{ route('admin.sales.cancel', $sale) }}" onsubmit="return confirm('Cancel sale and restore stock?')">@csrf<button class="btn btn-outline-danger w-100">Cancel Sale</button></form>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="payModal" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('admin.sales.pay', $sale) }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Record Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <label class="form-label">Amount (balance {{ $cur }} {{ number_format((float) $sale->balance, 2) }})</label>
            <input type="number" step="0.01" min="0.01" max="{{ $sale->balance }}" name="amount" class="form-control mb-2" value="{{ $sale->balance }}" required>
            <label class="form-label">Method</label><select name="payment_method" class="form-select">@foreach (\App\Support\Settings::paymentMethods() as $m)<option>{{ $m }}</option>@endforeach</select>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Record</button></div>
    </form></div></div>
    <div class="modal fade" id="refundModal" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('admin.sales.refund', $sale) }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Refund</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <label class="form-label">Amount (paid {{ $cur }} {{ number_format((float) $sale->amount_paid, 2) }})</label>
            <input type="number" step="0.01" min="0.01" max="{{ $sale->amount_paid }}" name="amount" class="form-control mb-2" required>
            <label class="form-label">Reason *</label><textarea name="reason" class="form-control" required></textarea>
        </div>
        <div class="modal-footer"><button class="btn btn-danger">Refund</button></div>
    </form></div></div>
    <div class="modal fade" id="returnModal" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('admin.sales.return', $sale) }}" class="modal-content">@csrf
        <div class="modal-header"><h5 class="modal-title">Return Items</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            @foreach ($sale->items as $item)
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="flex-grow-1">{{ $item->product_name }} <span class="small text-muted">(sold {{ $item->quantity }})</span></div>
                    <input type="hidden" name="items[{{ $loop->index }}][sale_item_id]" value="{{ $item->id }}">
                    <input type="number" name="items[{{ $loop->index }}][quantity]" min="0" max="{{ $item->quantity }}" value="0" class="form-control" style="width:80px">
                </div>
            @endforeach
            <div class="form-check mb-2"><input type="checkbox" name="restock" value="1" class="form-check-input" id="restock" checked><label class="form-check-label" for="restock">Return items to stock</label></div>
            <label class="form-label">Reason *</label><textarea name="reason" class="form-control" required></textarea>
            <p class="small text-muted mt-2 mb-0">Note: returns restock items. Use Refund to return money.</p>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Record Return</button></div>
    </form></div></div>
</x-app-layout>
