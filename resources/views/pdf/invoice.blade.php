<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 0; }
        .header { border-bottom: 2px solid #1e3a8a; padding-bottom: 12px; margin-bottom: 14px; }
        .header img { max-height: 55px; }
        .clinic { font-size: 15px; font-weight: bold; color: #1e3a8a; }
        .muted { color: #6b7280; font-size: 10px; }
        .doc-title { font-size: 18px; font-weight: bold; color: #1e3a8a; text-align: right; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 0; font-size: 10px; }
        .items th { background: #1e3a8a; color: #fff; text-align: left; padding: 6px 8px; font-size: 10px; text-transform: uppercase; }
        .items td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        .right { text-align: right; }
        .group { background: #f3f4f6; font-weight: bold; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; }
        .totals td { padding: 3px 8px; }
        .grand { font-weight: bold; font-size: 13px; border-top: 2px solid #1e3a8a; }
        .pay-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 10px; margin-top: 14px; font-size: 10px; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
        .sig { margin-top: 30px; }
    </style>
</head>
<body>
    @php
        $logoPath = $clinic['clinic_logo'] ? public_path('storage/'.$clinic['clinic_logo']) : null;
        $services = $invoice->items->where('item_type', 'service');
        $products = $invoice->items->where('item_type', 'product');
    @endphp
    <table class="header"><tr>
        <td style="width:60%; border:none">
            @if ($logoPath && file_exists($logoPath))<img src="{{ $logoPath }}"><br>@endif
            <div class="clinic">{{ $clinic['clinic_name'] }}</div>
            <div class="muted">{{ $clinic['clinic_address'] }}{{ $clinic['clinic_phone'] ? ' · '.$clinic['clinic_phone'] : '' }}<br>{{ $clinic['clinic_email'] }}{{ $clinic['clinic_website'] ? ' · '.$clinic['clinic_website'] : '' }}</div>
            @if ($clinic['clinic_reg_number'] || $clinic['clinic_tax_id'])<div class="muted">{{ $clinic['clinic_reg_number'] ? 'Reg: '.$clinic['clinic_reg_number'] : '' }}{{ $clinic['clinic_tax_id'] ? ' · TIN: '.$clinic['clinic_tax_id'] : '' }}</div>@endif
        </td>
        <td style="border:none; vertical-align:top">
            <div class="doc-title">INVOICE</div>
            <table class="meta">
                <tr><td class="muted">Invoice No.</td><td class="right"><strong>{{ $invoice->invoice_reference }}</strong></td></tr>
                <tr><td class="muted">Issue Date</td><td class="right">{{ $invoice->issue_date->format('d M Y') }}</td></tr>
                <tr><td class="muted">Due Date</td><td class="right">{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}</td></tr>
                <tr><td class="muted">Status</td><td class="right">{{ strtoupper(str_replace('_', ' ', $invoice->status)) }}</td></tr>
            </table>
        </td>
    </tr></table>

    <table class="meta" style="margin-bottom:10px">
        <tr><td class="muted" style="width:15%">BILLED TO</td><td><strong>{{ $invoice->patient?->full_name }}</strong> ({{ $invoice->patient?->patient_number }})<br><span class="muted">{{ $invoice->patient?->phone }} {{ $invoice->patient?->address }}</span></td></tr>
    </table>

    <table class="items">
        <thead><tr><th>Description</th><th class="right" style="width:10%">Qty</th><th class="right" style="width:18%">Unit Price</th><th class="right" style="width:18%">Amount</th></tr></thead>
        <tbody>
            @if ($services->isNotEmpty())
                <tr class="group"><td colspan="4">Physiotherapy Services</td></tr>
                @foreach ($services as $item)
                    <tr><td>{{ $item->service_name }}<div class="muted">{{ $item->service_code }}</div></td><td class="right">{{ $item->quantity }}</td><td class="right">{{ number_format((float) $item->unit_price, 2) }}</td><td class="right">{{ number_format((float) $item->line_total, 2) }}</td></tr>
                @endforeach
            @endif
            @if ($products->isNotEmpty())
                <tr class="group"><td colspan="4">Medical Products</td></tr>
                @foreach ($products as $item)
                    <tr><td>{{ $item->service_name }}<div class="muted">{{ $item->service_code }}</div></td><td class="right">{{ $item->quantity }}</td><td class="right">{{ number_format((float) $item->unit_price, 2) }}</td><td class="right">{{ number_format((float) $item->line_total, 2) }}</td></tr>
                @endforeach
            @endif
        </tbody>
    </table>
    <table class="totals" style="margin-top:8px">
        <tr><td class="right muted">Subtotal</td><td class="right" style="width:140px">{{ $clinic['currency'] }} {{ number_format((float) $invoice->subtotal, 2) }}</td></tr>
        @if ((float) $invoice->discount_amount > 0)<tr><td class="right muted">Discount ({{ $invoice->discount_reason }})</td><td class="right">- {{ $clinic['currency'] }} {{ number_format((float) $invoice->discount_amount, 2) }}</td></tr>@endif
        <tr class="grand"><td class="right">TOTAL</td><td class="right">{{ $clinic['currency'] }} {{ number_format((float) $invoice->total, 2) }}</td></tr>
        <tr><td class="right muted">Amount Paid</td><td class="right">{{ $clinic['currency'] }} {{ number_format((float) $invoice->amount_paid, 2) }}</td></tr>
        <tr><td class="right muted"><strong>Balance Due</strong></td><td class="right"><strong>{{ $clinic['currency'] }} {{ number_format((float) $invoice->balance, 2) }}</strong></td></tr>
    </table>

    @if ($clinic['office_payment_number'] || $clinic['bank_name'])
        <div class="pay-box">
            <strong>PAYMENT OPTIONS</strong><br>
            @if ($clinic['office_payment_number'])
                Office Payment: <strong>{{ $clinic['office_payment_number'] }}</strong>{{ $clinic['office_payment_name'] ? ' ('.$clinic['office_payment_name'].')' : '' }}{{ $clinic['office_payment_instructions'] ? ' — '.$clinic['office_payment_instructions'] : '' }}<br>
            @endif
            @if ($clinic['bank_name'])
                Bank Transfer: {{ $clinic['bank_name'] }} — {{ $clinic['bank_account_name'] }} — Acc: <strong>{{ $clinic['bank_account_number'] }}</strong>{{ $clinic['bank_branch'] ? ' ('.$clinic['bank_branch'].')' : '' }}{{ $clinic['bank_instructions'] ? ' — '.$clinic['bank_instructions'] : '' }}<br>
            @endif
            Cash: payable at the clinic office.<br>
            Quote invoice number <strong>{{ $invoice->invoice_reference }}</strong> as your payment reference.
        </div>
    @endif

    @if ($invoice->terms)<div class="muted" style="margin-top:10px"><strong>Terms:</strong> {{ $invoice->terms }}</div>@endif
    <div class="sig"><table><tr><td style="border:none; width:50%" class="muted">{{ $clinic['signature_label'] ?: 'Authorized Signature' }}: ____________________</td><td style="border:none" class="right muted">Date: ____________</td></tr></table></div>
    <div class="footer">{{ $clinic['receipt_footer'] ?: 'Thank you for your business!' }} · {{ $invoice->invoice_reference }}</div>
</body>
</html>
