<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $payment->payment_reference }}</title>
    <style>
        body { font-family: sans-serif; color: #111; font-size: 12px; margin: 30px; }
        h1 { font-size: 18px; margin-bottom: 2px; }
        h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        td, th { border: 1px solid #ccc; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #f4f4f4; font-size: 11px; width: 25%; }
        .header { border-bottom: 2px solid #02481f; padding-bottom: 10px; margin-bottom: 15px; }
        .amount { font-size: 20px; font-weight: bold; }
        .sig { margin-top: 50px; }
    </style>
</head>
<body onload="window.print()">
    @php $cur = \App\Support\Settings::get('currency', 'TZS'); @endphp
    <div class="header">
        <h1>{{ \App\Support\Settings::get('clinic_name', config('app.name')) }}</h1>
        <div class="muted">{{ \App\Support\Settings::get('clinic_address') }} {{ \App\Support\Settings::get('clinic_phone') }} {{ \App\Support\Settings::get('clinic_email') }}</div>
    </div>

    <h2>Payment Receipt — {{ $payment->payment_reference }}</h2>
    <table>
        <tr><th>Received From</th><td>{{ $payment->patient?->full_name }} ({{ $payment->patient?->patient_number }})</td></tr>
        <tr><th>Invoice</th><td>{{ $payment->invoice?->invoice_reference }}</td></tr>
        <tr><th>Amount</th><td class="amount">{{ $cur }} {{ number_format((float) $payment->amount, 2) }}</td></tr>
        <tr><th>Method</th><td>{{ $payment->payment_method }}</td></tr>
        <tr><th>Date</th><td>{{ $payment->payment_date->format('d/m/Y') }}</td></tr>
        <tr><th>Received By</th><td>{{ $payment->receiver?->name ?? '—' }}</td></tr>
        <tr><th>Invoice Balance</th><td>{{ $cur }} {{ number_format((float) $payment->invoice?->balance, 2) }}</td></tr>
        @if ($payment->notes)
            <tr><th>Notes</th><td>{{ $payment->notes }}</td></tr>
        @endif
    </table>

    <div class="sig">
        <div>{{ \App\Support\Settings::get('report_signature') }}</div>
        <div class="muted">Signature: ______________________</div>
    </div>
    <p class="muted" style="margin-top:20px;">Printed {{ now()->format('d/m/Y H:i') }} · {{ \App\Support\Settings::get('report_footer') }}</p>
</body>
</html>
