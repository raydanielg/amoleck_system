<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_reference }}</title>
    <style>
        body { font-family: sans-serif; color: #111; font-size: 12px; margin: 30px; }
        h1 { font-size: 18px; margin-bottom: 2px; }
        h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        td, th { border: 1px solid #ccc; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #f4f4f4; font-size: 11px; }
        .header { border-bottom: 2px solid #0a2540; padding-bottom: 10px; margin-bottom: 15px; }
        .totals td { border: none; padding: 3px 7px; }
        .sig { margin-top: 50px; }
        .right { text-align: right; }
    </style>
</head>
<body onload="window.print()">
    @php $cur = \App\Support\Settings::get('currency', 'TZS'); @endphp
    <div class="header">
        <h1>{{ \App\Support\Settings::get('clinic_name', config('app.name')) }}</h1>
        <div class="muted">{{ \App\Support\Settings::get('clinic_address') }} {{ \App\Support\Settings::get('clinic_phone') }} {{ \App\Support\Settings::get('clinic_email') }}</div>
    </div>

    <h2>Invoice — {{ $invoice->invoice_reference }} ({{ ucfirst(str_replace('_', ' ', $invoice->status)) }})</h2>
    <table>
        <tr><th style="width:25%;">Patient</th><td>{{ $invoice->patient?->full_name }} ({{ $invoice->patient?->patient_number }})</td></tr>
        <tr><th>Issue Date</th><td>{{ $invoice->issue_date->format('d/m/Y') }}</td></tr>
        <tr><th>Issued By</th><td>{{ $invoice->issuer?->name ?? '—' }}</td></tr>
        @if ($invoice->visit)
            <tr><th>Visit Reference</th><td>{{ $invoice->visit->visit_reference }}</td></tr>
        @endif
    </table>

    <h2>Items</h2>
    <table>
        <tr><th>Service</th><th class="right">Unit Price</th><th class="right">Qty</th><th class="right">Total</th></tr>
        @foreach ($invoice->items as $item)
            <tr>
                <td>{{ $item->service_name }} {{ $item->service_code ? '('.$item->service_code.')' : '' }}</td>
                <td class="right">{{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="right">{{ $item->quantity }}</td>
                <td class="right">{{ number_format((float) $item->line_total, 2) }}</td>
            </tr>
        @endforeach
    </table>

    <table class="totals" style="width: 50%; margin-left: auto;">
        <tr><td class="right">Subtotal</td><td class="right">{{ $cur }} {{ number_format((float) $invoice->subtotal, 2) }}</td></tr>
        @if ((float) $invoice->discount_amount > 0)
            <tr><td class="right">Discount</td><td class="right">- {{ $cur }} {{ number_format((float) $invoice->discount_amount, 2) }}</td></tr>
        @endif
        <tr><td class="right"><strong>Total</strong></td><td class="right"><strong>{{ $cur }} {{ number_format((float) $invoice->total, 2) }}</strong></td></tr>
        <tr><td class="right">Paid</td><td class="right">{{ $cur }} {{ number_format((float) $invoice->amount_paid, 2) }}</td></tr>
        <tr><td class="right"><strong>Balance</strong></td><td class="right"><strong>{{ $cur }} {{ number_format((float) $invoice->balance, 2) }}</strong></td></tr>
    </table>

    @if ($invoice->payments->isNotEmpty())
        <h2>Payments</h2>
        <table>
            <tr><th>Reference</th><th>Date</th><th>Method</th><th class="right">Amount</th></tr>
            @foreach ($invoice->payments as $payment)
                <tr>
                    <td>{{ $payment->payment_reference }}</td>
                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                    <td>{{ $payment->payment_method }}</td>
                    <td class="right">{{ number_format((float) $payment->amount, 2) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="sig">
        <div>{{ \App\Support\Settings::get('report_signature') }}</div>
        <div class="muted">Signature: ______________________</div>
    </div>
    <p class="muted" style="margin-top:20px;">Printed {{ now()->format('d/m/Y H:i') }} · {{ \App\Support\Settings::get('report_footer') }}</p>
</body>
</html>
