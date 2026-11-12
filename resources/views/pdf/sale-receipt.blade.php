<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 0; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #16a34a; padding-bottom: 12px; }
        .header img { max-height: 60px; margin-bottom: 6px; }
        .clinic { font-size: 16px; font-weight: bold; }
        .muted { color: #6b7280; font-size: 10px; }
        .title { background: #16a34a; color: #fff; padding: 4px 10px; display: inline-block; font-size: 10px; letter-spacing: 1px; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f3f4f6; text-align: left; padding: 5px 8px; font-size: 10px; text-transform: uppercase; }
        td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
        .right { text-align: right; }
        .totals td { border: none; padding: 3px 8px; }
        .grand { font-weight: bold; font-size: 13px; border-top: 1px solid #16a34a; }
        .footer { margin-top: 24px; text-align: center; font-size: 10px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
        .meta td { border: none; padding: 2px 0; }
    </style>
</head>
<body>
    @php $logoPath = $clinic['clinic_logo'] ? public_path('storage/'.$clinic['clinic_logo']) : null; @endphp
    <div class="header">
        @if ($logoPath && file_exists($logoPath))<img src="{{ $logoPath }}" alt="">@endif
        <div class="clinic">{{ $clinic['clinic_name'] }}</div>
        <div class="muted">{{ $clinic['clinic_address'] }}{{ $clinic['clinic_phone'] ? ' · '.$clinic['clinic_phone'] : '' }}{{ $clinic['clinic_email'] ? ' · '.$clinic['clinic_email'] : '' }}</div>
        @if ($clinic['clinic_reg_number'] || $clinic['clinic_tax_id'])
            <div class="muted">{{ $clinic['clinic_reg_number'] ? 'Reg: '.$clinic['clinic_reg_number'] : '' }}{{ $clinic['clinic_tax_id'] ? ' · TIN: '.$clinic['clinic_tax_id'] : '' }}</div>
        @endif
        <div class="title">SALES RECEIPT</div>
    </div>
    <table class="meta">
        <tr><td class="muted">Receipt</td><td class="right"><strong>{{ $sale->sale_reference }}</strong></td><td class="muted">Date</td><td class="right">{{ $sale->created_at->format('d M Y H:i') }}</td></tr>
        <tr><td class="muted">Customer</td><td class="right">{{ $sale->customer?->name ?? $sale->customer_name ?? 'Walk-in' }}</td><td class="muted">Served by</td><td class="right">{{ $sale->seller?->name }}</td></tr>
    </table>
    <table>
        <thead><tr><th>Item</th><th class="right">Qty</th><th class="right">Unit Price</th><th class="right">Total</th></tr></thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr><td>{{ $item->product_name }}</td><td class="right">{{ $item->quantity }}</td><td class="right">{{ number_format((float) $item->unit_price, 2) }}</td><td class="right">{{ number_format((float) $item->line_total, 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>
    <table class="totals">
        <tr><td class="right muted">Subtotal</td><td class="right" style="width:120px">{{ $clinic['currency'] }} {{ number_format((float) $sale->subtotal, 2) }}</td></tr>
        @if ($sale->discount_amount > 0)<tr><td class="right muted">Discount ({{ $sale->discount_reason }})</td><td class="right">- {{ $clinic['currency'] }} {{ number_format((float) $sale->discount_amount, 2) }}</td></tr>@endif
        @if ($sale->tax_amount > 0)<tr><td class="right muted">Tax ({{ $sale->tax_percent }}%)</td><td class="right">{{ $clinic['currency'] }} {{ number_format((float) $sale->tax_amount, 2) }}</td></tr>@endif
        <tr class="grand"><td class="right">TOTAL</td><td class="right">{{ $clinic['currency'] }} {{ number_format((float) $sale->total, 2) }}</td></tr>
        <tr><td class="right muted">Paid ({{ $sale->payments->last()?->payment_method ?? '—' }})</td><td class="right">{{ $clinic['currency'] }} {{ number_format((float) $sale->amount_paid, 2) }}</td></tr>
        @if ($sale->balance > 0)<tr><td class="right muted">Balance</td><td class="right">{{ $clinic['currency'] }} {{ number_format((float) $sale->balance, 2) }}</td></tr>@endif
    </table>
    <div class="footer">
        {{ $clinic['receipt_footer'] ?: 'Thank you for your business!' }}<br>
        @if ($clinic['signature_label'])
            {{ $clinic['signature_label'] }}: ____________________
        @endif
    </div>
</body>
</html>
