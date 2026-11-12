<x-app-layout>
    <x-slot name="header">{{ __('Accounting Dashboard') }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('accounting.journals') }}" class="btn btn-outline-secondary btn-sm">Journal</a>
        <a href="{{ route('accounting.reports') }}" class="btn btn-outline-secondary btn-sm">Reports</a>
    </x-slot>
    <div class="card shadow-sm border-0 mb-3"><div class="card-body"><form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small">From</label><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
        <div class="col-md-3"><label class="form-label small">To</label><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
        <div class="col-md-2"><button class="btn btn-outline-secondary">Apply</button></div>
    </form></div></div>
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Revenue</div><div class="stat-value text-success">{{ number_format($pnl['revenue']) }}</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">COGS</div><div class="stat-value">{{ number_format($pnl['cogs']) }}</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Expenses</div><div class="stat-value text-danger">{{ number_format($pnl['expenses']) }}</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Net Profit</div><div class="stat-value {{ $pnl['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($pnl['net']) }}</div></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Cash</div><div class="stat-value">{{ number_format($balances['cash']) }}</div></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Bank</div><div class="stat-value">{{ number_format($balances['bank']) }}</div></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Receivable</div><div class="stat-value text-warning">{{ number_format($balances['receivable']) }}</div></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Payable</div><div class="stat-value">{{ number_format($balances['payable']) }}</div></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Salary Payable</div><div class="stat-value">{{ number_format($balances['salary_payable']) }}</div></div></div>
        <div class="col-md-2"><div class="stat-card"><div class="stat-label">Pending Verify</div><div class="stat-value text-info">{{ $pendingVerification }}</div></div></div>
    </div>
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100"><div class="card-header bg-white fw-semibold">Receivables Ageing</div><div class="card-body">
                @foreach (['current'=>'Current','30'=>'1–30d','60'=>'31–60d','90'=>'61–90d','90+'=>'Over 90d'] as $k => $label)
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">{{ $label }}</span><strong>{{ number_format($ar['buckets'][$k]) }}</strong></div>
                @endforeach
            </div></div>
        </div>
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100"><div class="card-header bg-white fw-semibold">Outstanding Invoices</div>
                <div class="table-responsive"><table class="table table-sm mb-0"><thead class="table-light"><tr><th>Invoice</th><th>Patient</th><th>Due</th><th class="text-end">Balance</th></tr></thead>
                <tbody>@forelse ($ar['invoices'] as $inv)
                    <tr><td class="small">{{ $inv->invoice_reference }}</td><td>{{ $inv->patient?->full_name }}</td><td class="small {{ $inv->due_date && $inv->due_date->isPast() ? 'text-danger' : 'text-muted' }}">{{ $inv->due_date?->format('d M Y') }}</td><td class="text-end fw-semibold">{{ number_format((float) $inv->balance) }}</td></tr>
                @empty<tr><td colspan="4" class="text-center text-muted py-3">No outstanding invoices.</td></tr>@endforelse</tbody></table></div>
            </div>
        </div>
    </div>
</x-app-layout>
