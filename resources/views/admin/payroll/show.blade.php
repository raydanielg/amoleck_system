<x-app-layout>
    <x-slot name="header">{{ __('Payroll '.$batch->reference) }}</x-slot>
    <x-slot name="headerActions">
        @if ($batch->status === 'draft')
            <form method="POST" action="{{ route('admin.payroll.approve', $batch) }}">@csrf<button class="btn btn-warning btn-sm">Approve Payroll</button></form>
        @endif
        @if ($batch->status === 'paid')
            <form method="POST" action="{{ route('admin.payroll.close', $batch) }}">@csrf<button class="btn btn-dark btn-sm">Close &amp; Lock</button></form>
        @endif
        <a href="{{ route('admin.payroll.index') }}" class="btn btn-outline-secondary btn-sm">All Batches</a>
    </x-slot>
    <div class="d-flex gap-2 mb-3 flex-wrap">
        @foreach (['draft'=>'1. Calculated','approved'=>'2. Approved','paid'=>'3. Paid','closed'=>'4. Closed'] as $st => $label)
            <span class="badge {{ $batch->status === $st ? 'text-bg-primary' : (array_search($batch->status, array_keys(['draft','approved','paid','closed'])) > array_search($st, array_keys(['draft','approved','paid','closed'])) ? 'text-bg-success' : 'text-bg-light border text-dark') }}">{{ $label }}</span>
        @endforeach
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Gross</div><div class="stat-value">{{ number_format((float) $batch->total_gross) }}</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Deductions</div><div class="stat-value text-danger">{{ number_format((float) $batch->total_deductions) }}</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Net Payable</div><div class="stat-value text-primary">{{ number_format((float) $batch->total_net) }}</div></div></div>
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Employer Contribs</div><div class="stat-value text-muted">{{ number_format((float) $batch->total_employer) }}</div></div></div>
    </div>
    <div class="card shadow-sm border-0">
        <div class="table-responsive"><table class="table table-hover mb-0 align-middle">
            <thead class="table-light"><tr><th>Employee</th><th>Basic</th><th>Allow.</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Paid</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>@foreach ($batch->items as $it)
                <tr>
                    <td><div class="fw-semibold">{{ $it->employee->full_name }}</div><div class="small text-muted">{{ $it->employee->employee_number }} · {{ $it->employee->department?->name }}</div></td>
                    <td>{{ number_format((float) $it->basic) }}</td><td>{{ number_format((float) $it->allowances) }}</td>
                    <td>{{ number_format((float) $it->gross) }}</td>
                    <td>
                        {{ number_format((float) $it->total_deductions) }}
                        @foreach ($it->deductions ?? [] as $d)<div class="small text-muted">{{ $d['name'] }}: {{ number_format($d['amount']) }}</div>@endforeach
                    </td>
                    <td class="fw-semibold">{{ number_format((float) $it->net) }}</td>
                    <td>{{ number_format((float) $it->paid) }}</td>
                    <td><span class="badge {{ ['unpaid'=>'text-bg-secondary','partial'=>'text-bg-warning','paid'=>'text-bg-success'][$it->payment_status] }}">{{ $it->payment_status }}</span></td>
                    <td class="text-end text-nowrap">
                        @if (in_array($batch->status, ['approved','paid']) && $it->payment_status !== 'paid')
                            <button class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" data-bs-target="#pay{{ $it->id }}">Pay</button>
                        @endif
                        <a href="{{ route('admin.payroll.payslip', $it) }}" class="btn btn-sm btn-outline-secondary">Payslip</a>
                    </td>
                </tr>
                <div class="offcanvas offcanvas-end" id="pay{{ $it->id }}" style="width:360px">
                    <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Pay {{ $it->employee->full_name }}</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
                    <form method="POST" action="{{ route('admin.payroll.items.pay', $it) }}" class="offcanvas-body d-flex flex-column gap-2">
                        @csrf
                        <div class="small text-muted">Net salary: <strong>{{ number_format((float) $it->net) }}</strong> · Outstanding: <strong>{{ number_format((float) $it->net - (float) $it->paid) }}</strong></div>
                        <label class="form-label">Amount</label><input type="number" step="0.01" name="amount" class="form-control" value="{{ (float) $it->net - (float) $it->paid }}" required>
                        <label class="form-label">Method</label><select name="method" class="form-select"><option value="bank">Bank transfer</option><option value="cash">Cash</option><option value="mobile">Mobile money</option></select>
                        <label class="form-label">Reference</label><input name="reference" class="form-control" placeholder="Transaction ref">
                        <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Record Payment</button></div>
                    </form>
                </div>
            @endforeach</tbody>
        </table></div>
    </div>
</x-app-layout>
