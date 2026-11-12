<x-app-layout>
    <x-slot name="header">{{ __('Operations Overview') }}</x-slot>
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Active Employees</div><div class="stat-value">{{ $hr['employees'] }}</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">On Leave / Pending</div><div class="stat-value">{{ $hr['on_leave'] }} <span class="small text-muted">/ {{ $hr['pending_leave'] }}</span></div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Contracts Expiring (60d)</div><div class="stat-value text-warning">{{ $hr['contracts_expiring'] }}</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Awaiting Payroll Pay</div><div class="stat-value">{{ $payroll['pending_paid'] }}</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Assets</div><div class="stat-value">{{ $assets['total'] }}</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Asset Value</div><div class="stat-value">{{ number_format((float) $assets['value']) }}</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Warranty Alerts</div><div class="stat-value text-warning">{{ $assets['warranty'] }}</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Expiring / Expired Batches</div><div class="stat-value text-danger">{{ $inventory['expiring'] }} / {{ $inventory['expired'] }}</div></div></div>
    </div>
    @if ($payroll['last_batch'])
        <div class="card shadow-sm border-0"><div class="card-body d-flex justify-content-between align-items-center">
            <div><strong>Latest payroll:</strong> {{ $payroll['last_batch']->reference }} — <span class="badge text-bg-info">{{ $payroll['last_batch']->status }}</span>
            Net {{ number_format((float) $payroll['last_batch']->total_net) }}</div>
            <a href="{{ route('admin.payroll.show', $payroll['last_batch']) }}" class="btn btn-sm btn-outline-primary">Open</a>
        </div></div>
    @endif
</x-app-layout>
