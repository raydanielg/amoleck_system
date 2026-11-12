<x-app-layout>
    <x-slot name="header">{{ __('Financial Reports') }}</x-slot>
    <div class="card shadow-sm border-0 mb-3"><div class="card-body"><form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small">From</label><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label small">To</label><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
        <div class="col-md-2"><button class="btn btn-outline-secondary">Apply</button></div>
    </form></div></div>
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100"><div class="card-header bg-white fw-semibold">Profit &amp; Loss</div><div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td>Revenue</td><td class="text-end">{{ number_format($pnl['revenue']) }}</td></tr>
                    <tr><td>Cost of Goods Sold</td><td class="text-end">({{ number_format($pnl['cogs']) }})</td></tr>
                    <tr class="fw-semibold border-top"><td>Gross Profit</td><td class="text-end">{{ number_format($pnl['gross']) }}</td></tr>
                    <tr><td>Operating Expenses</td><td class="text-end">({{ number_format($pnl['expenses'] - $pnl['cogs']) }})</td></tr>
                    <tr class="fw-bold border-top"><td class="{{ $pnl['net'] >= 0 ? 'text-success' : 'text-danger' }}">Net Profit / (Loss)</td><td class="text-end {{ $pnl['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($pnl['net']) }}</td></tr>
                </table>
            </div></div>
            <div class="card shadow-sm border-0 mt-3"><div class="card-header bg-white fw-semibold">Recent Payroll (aggregates)</div>
                <ul class="list-group list-group-flush small">
                    @foreach ($payroll as $b)<li class="list-group-item d-flex justify-content-between"><span>{{ $b->reference }}</span><span>Net {{ number_format((float) $b->total_net) }} · Employer {{ number_format((float) $b->total_employer) }} <span class="badge text-bg-light border">{{ $b->status }}</span></span></li>@endforeach
                </ul>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100"><div class="card-header bg-white fw-semibold">Trial Balance</div>
                <div class="table-responsive"><table class="table table-sm mb-0"><thead class="table-light"><tr><th>Code</th><th>Account</th><th>Type</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
                <tbody>@php $td=0;$tc=0; @endphp
                    @forelse ($trial as $r)
                        @php $td+=$r['debit'];$tc+=$r['credit']; @endphp
                        <tr><td class="small text-muted">{{ $r['code'] }}</td><td>{{ $r['name'] }}</td><td><span class="badge text-bg-light border">{{ $r['type'] }}</span></td><td class="text-end">{{ $r['debit'] ? number_format($r['debit']) : '—' }}</td><td class="text-end">{{ $r['credit'] ? number_format($r['credit']) : '—' }}</td></tr>
                    @empty<tr><td colspan="5" class="text-center text-muted py-3">No posted transactions.</td></tr>@endforelse
                    @if ($trial)<tr class="fw-bold border-top"><td colspan="3">Totals</td><td class="text-end">{{ number_format($td) }}</td><td class="text-end">{{ number_format($tc) }}</td></tr>@endif
                </tbody></table></div>
            </div>
        </div>
    </div>
</x-app-layout>
