<x-app-layout>
    <x-slot name="header">{{ __('Expense Budgets') }}</x-slot>
    <x-slot name="headerActions"><button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#budgetDrawer"><i class="hgi-stroke hgi-plus-sign me-1"></i>Set Budget</button></x-slot>
    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom"><form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small">Month</label><select name="month" class="form-select">@for ($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected($m==$month)>{{ \Carbon\Carbon::create(null,$m)->format('F') }}</option>@endfor</select></div>
            <div class="col-md-3"><label class="form-label small">Year</label><input type="number" name="year" class="form-control" value="{{ $year }}"></div>
            <div class="col-md-2"><button class="btn btn-outline-secondary">View</button></div>
        </form></div>
        <div class="table-responsive"><table class="table mb-0 align-middle">
            <thead class="table-light"><tr><th>Category</th><th>Branch</th><th>Budget</th><th>Actual</th><th>Utilization</th></tr></thead>
            <tbody>@forelse ($budgets as $b)
                @php $act = (float) ($actual[$b->category] ?? 0); $pct = $b->amount > 0 ? min(100, round($act / (float) $b->amount * 100)) : 0; @endphp
                <tr><td class="fw-semibold">{{ $b->category }}</td><td>{{ $b->branch?->name ?? 'All' }}</td><td>{{ number_format((float) $b->amount) }}</td><td>{{ number_format($act) }}</td>
                <td><div class="progress" style="height:8px"><div class="progress-bar {{ $pct >= 100 ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-success') }}" style="width:{{ $pct }}%"></div></div><span class="small text-muted">{{ $pct }}%</span></td></tr>
            @empty<tr><td colspan="5" class="text-center text-muted py-4">No budgets for this period.</td></tr>@endforelse</tbody>
        </table></div>
    </div>
    <div class="offcanvas offcanvas-end" id="budgetDrawer" style="width:380px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Set Budget</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('admin.budgets.store') }}" class="offcanvas-body d-flex flex-column gap-2">
            @csrf
            <div class="row g-2"><div class="col-6"><label class="form-label">Month</label><select name="month" class="form-select">@for ($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected($m==$month)>{{ \Carbon\Carbon::create(null,$m)->format('F') }}</option>@endfor</select></div>
            <div class="col-6"><label class="form-label">Year</label><input type="number" name="year" class="form-control" value="{{ $year }}"></div></div>
            <label class="form-label">Category</label><input name="category" class="form-control" list="expCats" required><datalist id="expCats">@foreach ($categories as $c)<option value="{{ $c }}">@endforeach</datalist>
            <label class="form-label">Branch (optional)</label><select name="branch_id" class="form-select"><option value="">All branches</option>@foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select>
            <label class="form-label">Amount</label><input type="number" step="0.01" name="amount" class="form-control" required>
            <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Save Budget</button></div>
        </form>
    </div>
</x-app-layout>
