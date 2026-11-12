<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 20px 24px; }
        .topbar { height: 6px; background: #0a2540; margin-bottom: 18px; }
        .header { padding-bottom: 12px; margin-bottom: 14px; }
        .header img { max-height: 52px; margin-bottom: 4px; }
        .clinic { font-size: 16px; font-weight: bold; color: #0a2540; }
        .muted { color: #6b7280; font-size: 10px; }
        .doc-title { font-size: 22px; font-weight: bold; color: #0f2a43; text-align: right; letter-spacing: 1px; }
        .doc-title span { color: #0a2540; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 0; font-size: 10px; }
        .status { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-partial { background: #fef9c3; color: #854d0e; }
        .status-unpaid { background: #fee2e2; color: #991b1b; }
        .items th { background: #0f2a43; color: #fff; text-align: left; padding: 7px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; }
        .items td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; }
        .items tr:nth-child(even) td { background: #fafbfc; }
        .right { text-align: right; }
        .group td { background: #eaf4ee !important; color: #0a2540; font-weight: bold; font-size: 9px; text-transform: uppercase; letter-spacing: .8px; padding: 5px 8px; }
        .totals td { padding: 4px 8px; }
        .grand td { font-weight: bold; font-size: 13px; color: #0f2a43; border-top: 2px solid #0a2540; padding-top: 6px; }
        .balance td { color: #b91c1c; font-weight: bold; }
        .pay-box { background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #0a2540; padding: 9px 12px; margin-top: 16px; font-size: 10px; }
        .pay-box .t { color: #0a2540; font-weight: bold; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
        .footer { margin-top: 24px; text-align: center; font-size: 9px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 10px; }
        .sig { margin-top: 34px; }
        .watermark { position: absolute; top: 42%; left: 20%; font-size: 60px; color: #f0fdf4; font-weight: bold; transform: rotate(-20deg); z-index: -1; }
    </style>
</head>
<body>
    @php
        $logoSrc = null;
        foreach ([$clinic['clinic_logo'] ? storage_path('app/public/'.$clinic['clinic_logo']) : null,
                  $clinic['clinic_logo'] ? public_path('storage/'.$clinic['clinic_logo']) : null,
                  public_path('amoleck-logo.png')] as $p) {
            if ($p && file_exists($p)) {
                $logoSrc = 'data:image/'.pathinfo($p, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents($p));
                break;
            }
        }
        $services = $invoice->items->where('item_type', 'service');
        $products = $invoice->items->where('item_type', 'product');
        $st = strtolower($invoice->status);
        $statusClass = str_contains($st, 'paid') && !str_contains($st, 'partial') ? 'status-paid' : (str_contains($st, 'partial') ? 'status-partial' : 'status-unpaid');
    @endphp

    <div class="topbar"></div>
    <table class="header"><tr>
        <td style="width:60%; border:none; vertical-align:top">
            @if ($logoSrc)<img src="{{ $logoSrc }}" style="max-height:52px"><br>@endif
            <div class="clinic">{{ $clinic['clinic_name'] }}</div>
            <div class="muted">{{ $clinic['clinic_address'] }}{{ $clinic['clinic_phone'] ? ' · '.$clinic['clinic_phone'] : '' }}<br>{{ $clinic['clinic_email'] }}{{ $clinic['clinic_website'] ? ' · '.$clinic['clinic_website'] : '' }}</div>
            @if ($clinic['clinic_reg_number'] || $clinic['clinic_tax_id'])<div class="muted">{{ $clinic['clinic_reg_number'] ? 'Reg: '.$clinic['clinic_reg_number'] : '' }}{{ $clinic['clinic_tax_id'] ? ' · TIN: '.$clinic['clinic_tax_id'] : '' }}</div>@endif
        </td>
        <td style="border:none; vertical-align:top">
            <div class="doc-title">INVOICE<span>.</span></div>
            <table class="meta">
                <tr><td class="muted">Invoice No.</td><td class="right"><strong>{{ $invoice->invoice_reference }}</strong></td></tr>
                <tr><td class="muted">Issue Date</td><td class="right">{{ $invoice->issue_date->format('d M Y') }}</td></tr>
                <tr><td class="muted">Due Date</td><td class="right">{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}</td></tr>
                <tr><td class="muted">Status</td><td class="right"><span class="status {{ $statusClass }}">{{ strtoupper(str_replace('_', ' ', $invoice->status)) }}</span></td></tr>
            </table>
        </td>
    </tr></table>

    <table class="meta" style="margin-bottom:12px; background:#f8fafc; padding:8px">
        <tr><td class="muted" style="width:15%; padding-left:8px">BILLED TO</td><td><strong>{{ $invoice->patient?->full_name }}</strong>{{ $invoice->patient?->patient_number ? ' ('.$invoice->patient->patient_number.')' : '' }}<br><span class="muted">{{ $invoice->patient?->phone }}{{ $invoice->patient?->address ? ' · '.$invoice->patient->address : '' }}</span></td></tr>
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

    <table class="totals" style="margin-top:10px">
        <tr><td class="right muted">Subtotal</td><td class="right" style="width:150px">{{ $clinic['currency'] }} {{ number_format((float) $invoice->subtotal, 2) }}</td></tr>
        @if ((float) $invoice->discount_amount > 0)<tr><td class="right muted">Discount ({{ $invoice->discount_reason }})</td><td class="right">- {{ $clinic['currency'] }} {{ number_format((float) $invoice->discount_amount, 2) }}</td></tr>@endif
        @if ((float) ($invoice->tax_amount ?? 0) > 0)<tr><td class="right muted">Tax</td><td class="right">{{ $clinic['currency'] }} {{ number_format((float) $invoice->tax_amount, 2) }}</td></tr>@endif
        <tr class="grand"><td class="right">TOTAL</td><td class="right">{{ $clinic['currency'] }} {{ number_format((float) $invoice->total, 2) }}</td></tr>
        <tr><td class="right muted">Amount Paid</td><td class="right">{{ $clinic['currency'] }} {{ number_format((float) $invoice->amount_paid, 2) }}</td></tr>
        @if ((float) $invoice->balance > 0)<tr class="balance"><td class="right">Balance Due</td><td class="right">{{ $clinic['currency'] }} {{ number_format((float) $invoice->balance, 2) }}</td></tr>@endif
    </table>

    @if ($clinic['office_payment_number'] || $clinic['bank_name'])
        <div class="pay-box">
            <div class="t">Payment Options</div>
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
