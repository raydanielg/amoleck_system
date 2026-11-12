<x-app-layout>
    <x-slot name="header">{{ __('Payroll') }}</x-slot>
    <x-slot name="headerActions"><button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#batchDrawer"><i class="hgi-stroke hgi-plus-sign me-1"></i>New Payroll Batch</button></x-slot>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabBatches">Batches</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabRules">Payroll Rules</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabLoans">Loans &amp; Advances</a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="tabBatches">
            <div class="card shadow-sm border-0"><div class="table-responsive"><table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>Reference</th><th>Period</th><th>Employees</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Employer</th><th>Status</th><th></th></tr></thead>
                <tbody>@forelse ($batches as $b)
                    <tr><td class="fw-semibold">{{ $b->reference }}</td><td>{{ \Carbon\Carbon::create($b->year,$b->month)->format('M Y') }}</td><td>{{ $b->items()->count() }}</td>
                    <td>{{ number_format((float) $b->total_gross) }}</td><td>{{ number_format((float) $b->total_deductions) }}</td>
                    <td class="fw-semibold">{{ number_format((float) $b->total_net) }}</td><td>{{ number_format((float) $b->total_employer) }}</td>
                    <td><span class="badge {{ ['draft'=>'text-bg-secondary','approved'=>'text-bg-warning','paid'=>'text-bg-success','closed'=>'text-bg-dark'][$b->status] }}">{{ $b->status }}</span></td>
                    <td class="text-end"><a href="{{ route('admin.payroll.show', $b) }}" class="btn btn-sm btn-outline-primary">Open</a></td></tr>
                @empty<tr><td colspan="9" class="text-center text-muted py-4">No payroll batches.</td></tr>@endforelse</tbody>
            </table></div>@if ($batches->hasPages())<div class="card-footer bg-white">{{ $batches->links() }}</div>@endif</div>
        </div>
        <div class="tab-pane fade" id="tabRules">
            <div class="alert alert-warning small"><strong>Note:</strong> Statutory rates (tax, pension, NHIF/insurance) are fully configurable here. Verify all rates against current Tanzanian requirements with your accountant before production use.</div>
            <div class="card shadow-sm border-0">
                <div class="card-body border-bottom"><form method="POST" action="{{ route('admin.payroll.rules.store') }}" class="row g-2 align-items-end">@csrf
                    <div class="col-md-3"><label class="form-label small">Rule name</label><input name="name" class="form-control" placeholder="e.g. PAYE, NSSF" required></div>
                    <div class="col-md-2"><label class="form-label small">Applies to</label><select name="applies_to" class="form-select"><option value="employee">Employee deduction</option><option value="employer">Employer contribution</option></select></div>
                    <div class="col-md-2"><label class="form-label small">Method</label><select name="calc_method" class="form-select"><option value="fixed">Fixed amount</option><option value="percent_gross">% of gross</option><option value="percent_basic">% of basic</option></select></div>
                    <div class="col-md-2"><label class="form-label small">Value</label><input type="number" step="0.0001" name="value" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label small">Effective from</label><input type="date" name="effective_from" class="form-control"></div>
                    <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
                </form></div>
                <div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>Name</th><th>Applies to</th><th>Method</th><th>Value</th><th>Effective</th><th>Status</th><th></th></tr></thead>
                <tbody>@forelse ($rules as $r)<tr><td>{{ $r->name }}</td><td><span class="badge {{ $r->applies_to==='employer'?'text-bg-info':'text-bg-light border' }}">{{ $r->applies_to }}</span></td>
                    <td>{{ $r->calc_method }}</td><td>{{ $r->calc_method==='fixed' ? number_format((float)$r->value) : $r->value.'%' }}</td>
                    <td class="small text-muted">{{ $r->effective_from?->format('d M Y') ?? 'always' }}{{ $r->effective_to ? ' → '.$r->effective_to->format('d M Y') : '' }}</td>
                    <td><span class="badge {{ $r->is_active?'text-bg-success':'text-bg-secondary' }}">{{ $r->is_active?'Active':'Off' }}</span></td>
                    <td class="text-end"><form method="POST" action="{{ route('admin.payroll.rules.toggle', $r) }}">@csrf<button class="btn btn-sm btn-outline-secondary">{{ $r->is_active?'Disable':'Enable' }}</button></form></td></tr>
                @empty<tr><td colspan="7" class="text-center text-muted py-3">No rules — deductions/contributions will be zero until configured.</td></tr>@endforelse</tbody></table></div>
            </div>
        </div>
        <div class="tab-pane fade" id="tabLoans">
            <div class="card shadow-sm border-0">
                <div class="card-body border-bottom"><form method="POST" action="{{ route('admin.loans.store') }}" class="row g-2 align-items-end">@csrf
                    <div class="col-md-3"><label class="form-label small">Employee</label><select name="employee_id" class="form-select" required>@foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label small">Type</label><select name="kind" class="form-select"><option value="loan">Loan</option><option value="advance">Advance</option></select></div>
                    <div class="col-md-2"><label class="form-label small">Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
                    <div class="col-md-2"><label class="form-label small">Monthly installment</label><input type="number" step="0.01" name="monthly_installment" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label small">Reason</label><input name="reason" class="form-control"></div>
                    <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
                </form></div>
                <div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>Ref</th><th>Employee</th><th>Type</th><th>Amount</th><th>Installment</th><th>Balance</th><th>Status</th></tr></thead>
                <tbody>@forelse ($loans as $l)<tr><td class="small text-muted">{{ $l->reference }}</td><td>{{ $l->employee->full_name }}</td><td><span class="badge text-bg-light border">{{ $l->kind }}</span></td>
                    <td>{{ number_format((float) $l->amount) }}</td><td>{{ $l->monthly_installment ? number_format((float) $l->monthly_installment) : 'full' }}</td>
                    <td class="fw-semibold">{{ number_format((float) $l->balance) }}</td><td><span class="badge text-bg-info">{{ $l->status }}</span></td></tr>
                @empty<tr><td colspan="7" class="text-center text-muted py-3">No active loans.</td></tr>@endforelse</tbody></table></div>
            </div>
        </div>
    </div>
    <div class="offcanvas offcanvas-end" id="batchDrawer" style="width:380px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">New Payroll Batch</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('admin.payroll.batches.store') }}" class="offcanvas-body d-flex flex-column gap-2">
            @csrf
            <label class="form-label">Month</label><select name="month" class="form-select">@for ($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected($m==now()->month)>{{ \Carbon\Carbon::create(null,$m)->format('F') }}</option>@endfor</select>
            <label class="form-label">Year</label><input type="number" name="year" class="form-control" value="{{ now()->year }}" min="2020" max="2100">
            <p class="small text-muted">All active employees will be included with their current salary structures and effective payroll rules.</p>
            <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Calculate Payroll</button></div>
        </form>
    </div>
</x-app-layout>
