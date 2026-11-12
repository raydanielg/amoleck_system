<x-app-layout>
    <x-slot name="header">{{ __('Petty Cash') }}</x-slot>
    <x-slot name="headerActions"><button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#pcDrawer"><i class="hgi-stroke hgi-plus-sign me-1"></i>Record Transaction</button></x-slot>
    <div class="stat-card mb-3" style="max-width:280px"><div class="stat-label">Current Balance</div><div class="stat-value">{{ number_format((float) $balance, 2) }} TZS</div></div>
    <div class="card shadow-sm border-0"><div class="table-responsive"><table class="table mb-0">
        <thead class="table-light"><tr><th>Date</th><th>Description</th><th>Type</th><th class="text-end">Amount</th><th>By</th></tr></thead>
        <tbody>@forelse ($txns as $t)
            <tr><td class="small text-muted">{{ $t->date->format('d M Y') }}</td><td>{{ $t->description }}</td><td><span class="badge {{ $t->type==='expense'?'text-bg-danger':'text-bg-success' }}">{{ $t->type }}</span></td>
            <td class="text-end">{{ $t->type==='expense'?'-':'+' }}{{ number_format((float) $t->amount) }}</td><td class="small">{{ $t->recorder?->name }}</td></tr>
        @empty<tr><td colspan="5" class="text-center text-muted py-4">No transactions.</td></tr>@endforelse</tbody>
    </table></div>@if ($txns->hasPages())<div class="card-footer bg-white">{{ $txns->links() }}</div>@endif</div>
    <div class="offcanvas offcanvas-end" id="pcDrawer" style="width:380px">
        <div class="offcanvas-header border-bottom"><h5 class="offcanvas-title">Petty Cash Transaction</h5><button class="btn-close" data-bs-dismiss="offcanvas"></button></div>
        <form method="POST" action="{{ route('admin.pettycash.store') }}" class="offcanvas-body d-flex flex-column gap-2">
            @csrf
            <label class="form-label">Type</label><select name="type" class="form-select"><option value="allocation">Fund allocation</option><option value="expense">Expense</option><option value="replenishment">Replenishment</option></select>
            <label class="form-label">Amount</label><input type="number" step="0.01" name="amount" class="form-control" required>
            <label class="form-label">Description</label><input name="description" class="form-control" required>
            <label class="form-label">Date</label><input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
            <div class="mt-auto pt-3 border-top"><button class="btn btn-primary w-100">Save</button></div>
        </form>
    </div>
</x-app-layout>
