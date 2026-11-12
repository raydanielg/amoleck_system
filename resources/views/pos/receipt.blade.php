@php $cur = \App\Support\Settings::get('currency', 'TZS'); @endphp
<x-app-layout>
    <x-slot name="header">{{ __('Sale '.$sale->sale_reference) }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('pos.index') }}" class="btn btn-outline-secondary btn-sm"><i class="hgi-stroke hgi-plus-sign me-1"></i>New Sale</a>
        <a href="{{ route('admin.sales.receipt-pdf', $sale) }}" class="btn btn-primary btn-sm"><i class="hgi-stroke hgi-download-01 me-1"></i>PDF Receipt</a>
        <a href="{{ route('admin.sales.show', $sale) }}" class="btn btn-outline-secondary btn-sm">Details</a>
    </x-slot>
    <div class="card shadow-sm border-0 mx-auto" style="max-width:640px">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                @php $logo = \App\Support\Settings::get('clinic_logo'); @endphp
                @if ($logo)<img src="{{ Storage::url($logo) }}" alt="" style="max-height:64px" class="mb-2">@endif
                <h5 class="mb-0">{{ \App\Support\Settings::get('clinic_name') }}</h5>
                <div class="small text-muted">{{ \App\Support\Settings::get('clinic_address') }} · {{ \App\Support\Settings::get('clinic_phone') }}</div>
                <span class="badge text-bg-success mt-2">SALES RECEIPT</span>
            </div>
            <div class="d-flex justify-content-between small text-muted mb-2">
                <span>{{ $sale->sale_reference }}</span><span>{{ $sale->created_at->format('d M Y H:i') }}</span>
            </div>
            <div class="small text-muted mb-3">Customer: {{ $sale->customer?->name ?? $sale->customer_name ?? 'Walk-in' }} · Served by {{ $sale->seller?->name }}</div>
            <table class="table table-sm">
                <thead class="table-light"><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    @foreach ($sale->items as $item)
                        <tr><td>{{ $item->product_name }}</td><td class="text-center">{{ $item->quantity }}</td><td class="text-end">{{ number_format((float) $item->unit_price, 2) }}</td><td class="text-end">{{ number_format((float) $item->line_total, 2) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
            <table class="table table-sm mb-3">
                <tr><td class="text-muted">Subtotal</td><td class="text-end">{{ $cur }} {{ number_format((float) $sale->subtotal, 2) }}</td></tr>
                @if ($sale->discount_amount > 0)<tr><td class="text-muted">Discount ({{ $sale->discount_reason }})</td><td class="text-end text-danger">- {{ $cur }} {{ number_format((float) $sale->discount_amount, 2) }}</td></tr>@endif
                @if ($sale->tax_amount > 0)<tr><td class="text-muted">Tax ({{ $sale->tax_percent }}%)</td><td class="text-end">{{ $cur }} {{ number_format((float) $sale->tax_amount, 2) }}</td></tr>@endif
                <tr class="fw-bold"><td>Total</td><td class="text-end">{{ $cur }} {{ number_format((float) $sale->total, 2) }}</td></tr>
                <tr><td class="text-muted">Paid ({{ $sale->payments->last()?->payment_method ?? '—' }})</td><td class="text-end">{{ $cur }} {{ number_format((float) $sale->amount_paid, 2) }}</td></tr>
                @if ($sale->balance > 0)<tr class="text-danger"><td>Balance</td><td class="text-end">{{ $cur }} {{ number_format((float) $sale->balance, 2) }}</td></tr>@endif
            </table>
            <p class="text-center small text-muted mb-0">{{ \App\Support\Settings::get('receipt_footer', 'Thank you for your business!') }}</p>
        </div>
    </div>
</x-app-layout>
