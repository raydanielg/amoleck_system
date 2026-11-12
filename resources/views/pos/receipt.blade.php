@php
    $cur = \App\Support\Settings::get('currency', 'TZS');
    $receiptItems = $sale->items->map(fn ($i) => ['name' => $i->product_name, 'qty' => (int) $i->quantity, 'price' => (float) $i->unit_price])->all();
    $extra = [
        'Customer' => $sale->customer?->name ?? $sale->customer_name ?? 'Walk-in',
        'Served by' => $sale->seller?->name ?? '—',
        'Paid ('.($sale->payments->last()?->payment_method ?? '—').')' => $cur.' '.number_format((float) $sale->amount_paid, 2),
    ];
    if ($sale->discount_amount > 0) $extra['Discount'] = '- '.$cur.' '.number_format((float) $sale->discount_amount, 2);
    if ($sale->balance > 0) $extra['Balance due'] = $cur.' '.number_format((float) $sale->balance, 2);
@endphp
<x-app-layout>
    <x-slot name="header">{{ __('Sale '.$sale->sale_reference) }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('pos.index') }}" class="btn btn-outline-secondary btn-sm"><i class="hgi-stroke hgi-plus-sign me-1"></i>New Sale</a>
        <a href="{{ route('admin.sales.receipt-pdf', $sale) }}" class="btn btn-primary btn-sm"><i class="hgi-stroke hgi-download-01 me-1"></i>PDF Receipt</a>
        <a href="{{ route('admin.sales.show', $sale) }}" class="btn btn-outline-secondary btn-sm">Details</a>
    </x-slot>
    <div class="d-flex justify-content-center py-3">
        <x-receipt-printer
            :order="$sale->sale_reference"
            :date="$sale->created_at->format('d M Y · H:i')"
            :items="$receiptItems"
            :subtotal="(float) $sale->subtotal"
            :tax="(float) $sale->tax_amount"
            :tax-label="'Tax '.($sale->tax_percent ?? 0).'%'"
            :total="(float) $sale->total"
            :currency="$cur"
            :extra="$extra" />
    </div>
    <p class="text-center small text-muted">Drag the receipt down to tear it off · Use PDF Receipt for printing</p>
</x-app-layout>
