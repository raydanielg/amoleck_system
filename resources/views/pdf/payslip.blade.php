<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
    .hdr { border-bottom: 3px solid #0f2a43; padding-bottom: 10px; margin-bottom: 16px; }
    h1 { font-size: 18px; margin: 0; color: #0f2a43; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 6px 8px; text-align: left; }
    .tbl th { background: #f1f5f9; border-bottom: 1px solid #cbd5e1; font-size: 11px; }
    .tbl td { border-bottom: 1px solid #e2e8f0; }
    .r { text-align: right; }
    .net { background: #0f2a43; color: #fff; font-size: 14px; }
    .net td { padding: 10px 8px; }
    .muted { color: #6b7280; font-size: 10px; }
</style></head><body>
    <div class="hdr">
        <h1>{{ $clinic['clinic_name'] ?? 'Amoleck Group Company LTD' }}</h1>
        <div class="muted">PAYSLIP — {{ \Carbon\Carbon::create($item->batch->year, $item->batch->month)->format('F Y') }} · {{ $item->batch->reference }} · CONFIDENTIAL</div>
    </div>
    <table style="margin-bottom:14px"><tr>
        <td><strong>{{ $item->employee->full_name }}</strong><br><span class="muted">{{ $item->employee->employee_number }} · {{ $item->employee->department?->name }} · {{ $item->employee->jobPosition?->title }}</span></td>
        <td class="r"><strong>Net Salary</strong><br>{{ number_format((float) $item->net, 2) }} TZS</td>
    </tr></table>
    <table class="tbl" style="margin-bottom:12px">
        <tr><th>Earnings</th><th class="r">Amount (TZS)</th></tr>
        <tr><td>Basic Salary</td><td class="r">{{ number_format((float) $item->basic, 2) }}</td></tr>
        <tr><td>Allowances &amp; Bonuses</td><td class="r">{{ number_format((float) $item->allowances, 2) }}</td></tr>
        <tr><td><strong>Gross Salary</strong></td><td class="r"><strong>{{ number_format((float) $item->gross, 2) }}</strong></td></tr>
    </table>
    <table class="tbl" style="margin-bottom:12px">
        <tr><th>Employee Deductions</th><th class="r">Amount (TZS)</th></tr>
        @forelse ($item->deductions ?? [] as $d)
            <tr><td>{{ $d['name'] }}</td><td class="r">{{ number_format($d['amount'], 2) }}</td></tr>
        @empty
            <tr><td colspan="2" class="muted">No deductions.</td></tr>
        @endforelse
        <tr><td><strong>Total Deductions</strong></td><td class="r"><strong>{{ number_format((float) $item->total_deductions, 2) }}</strong></td></tr>
    </table>
    @if (! empty($item->employer_contributions))
        <table class="tbl" style="margin-bottom:12px">
            <tr><th>Employer Contributions <span class="muted">(not deducted from pay)</span></th><th class="r">Amount (TZS)</th></tr>
            @foreach ($item->employer_contributions as $c)
                <tr><td>{{ $c['name'] }}</td><td class="r">{{ number_format($c['amount'], 2) }}</td></tr>
            @endforeach
        </table>
    @endif
    <table class="tbl net"><tr><td><strong>NET SALARY PAID</strong></td><td class="r"><strong>{{ number_format((float) $item->net, 2) }} TZS</strong></td></tr></table>
    <p class="muted" style="margin-top:10px">Paid: {{ number_format((float) $item->paid, 2) }} · Status: {{ $item->payment_status }} · Generated {{ now()->format('d M Y H:i') }}</p>
</body></html>
