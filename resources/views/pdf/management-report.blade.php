<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
    h1 { font-size: 18px; color: #0f2a43; border-bottom: 3px solid #0f2a43; padding-bottom: 8px; }
    h2 { font-size: 13px; color: #0f2a43; margin: 16px 0 6px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    td, th { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; text-align: left; }
    th { background: #f1f5f9; }
    .r { text-align: right; } .muted { color: #6b7280; font-size: 10px; }
</style></head><body>
    <h1>{{ $clinic }} — Monthly Management Report</h1>
    <p class="muted">Period: {{ $period }} · Generated {{ now()->format('d M Y H:i') }} · Confidential</p>
    <h2>Executive Summary</h2>
    <table><tr><th>Metric</th><th class="r">Value</th></tr>
        <tr><td>Total Revenue</td><td class="r">{{ number_format($kpis['revenue']) }}</td></tr>
        <tr><td>Total Expenses</td><td class="r">{{ number_format($kpis['expenses']) }}</td></tr>
        <tr><td>Net Profit (GL)</td><td class="r"><strong>{{ number_format($pnl['net']) }}</strong></td></tr>
        <tr><td>New Patients</td><td class="r">{{ $kpis['patients'] }}</td></tr>
        <tr><td>Appointments (completed)</td><td class="r">{{ $kpis['appointments'] }} ({{ $kpis['appointments_completed'] }})</td></tr>
        <tr><td>Visits</td><td class="r">{{ $kpis['visits'] }}</td></tr>
        <tr><td>Outstanding Balances</td><td class="r">{{ number_format($kpis['outstanding']) }}</td></tr>
    </table>
    <h2>Service Performance</h2>
    <table><tr><th>Service</th><th class="r">Sessions</th><th class="r">Revenue</th></tr>
        @forelse ($services as $s)<tr><td>{{ $s['label'] }}</td><td class="r">{{ $s['sessions'] }}</td><td class="r">{{ number_format($s['revenue']) }}</td></tr>
        @empty<tr><td colspan="3" class="muted">No service revenue.</td></tr>@endforelse
    </table>
    <h2>Branch Performance</h2>
    <table><tr><th>Branch</th><th class="r">Patients</th><th class="r">Revenue</th><th class="r">Expenses</th><th class="r">Staff</th></tr>
        @foreach ($branches as $b)<tr><td>{{ $b['name'] }}</td><td class="r">{{ $b['patients'] }}</td><td class="r">{{ number_format($b['revenue']) }}</td><td class="r">{{ number_format($b['expenses']) }}</td><td class="r">{{ $b['employees'] }}</td></tr>@endforeach
    </table>
    <h2>HR &amp; Inventory</h2>
    <table><tr><td>Active employees: {{ $hr['employees'] }} · On leave: {{ $hr['on_leave'] }} · Late today: {{ $hr['late_today'] }}</td></tr>
        <tr><td>Inventory value: {{ number_format($inventory['value']) }} · Low stock: {{ $inventory['low_stock'] }} · Out: {{ $inventory['out_of_stock'] }} · Expiring: {{ $inventory['batches_expiring'] }}</td></tr>
    </table>
    @if ($insights->isNotEmpty())
        <h2>Key Insights &amp; Alerts</h2>
        <table>@foreach ($insights as $i)<tr><td><strong>[{{ $i->category }}]</strong> {{ $i->title }}<br><span class="muted">{{ $i->detail }}</span></td></tr>@endforeach</table>
    @endif
</body></html>
