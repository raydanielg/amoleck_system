@php $cur = \App\Support\Settings::get('currency', 'TZS'); @endphp
<x-app-layout>
    <x-slot name="header">{{ __('Sales') }}</x-slot>
    <x-slot name="headerActions"><a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm"><i class="hgi-stroke hgi-shopping-cart-02 me-1"></i>Point of Sale</a></x-slot>
    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2">
                <div class="col-md-3"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Reference or customer..."></div>
                <div class="col-md-3"><select name="status" class="form-select"><option value="">All statuses</option>@foreach (['pending_payment','partially_paid','paid','partially_refunded','refunded','cancelled'] as $st)<option @selected(request('status') === $st)>{{ str_replace('_', ' ', $st) }}</option>@endforeach</select></div>
                <div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
                <div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
                <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>Reference</th><th>Customer</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th><th>Date</th><th></th></tr></thead>
                <tbody>
                    @forelse ($sales as $s)
                        <tr>
                            <td class="fw-semibold"><a href="{{ route('admin.sales.show', $s) }}">{{ $s->sale_reference }}</a></td>
                            <td>{{ $s->customer?->name ?? $s->customer_name ?? 'Walk-in' }}</td>
                            <td class="text-end">{{ number_format((float) $s->total, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $s->amount_paid, 2) }}</td>
                            <td class="text-end">{{ $s->balance > 0 ? number_format((float) $s->balance, 2) : '—' }}</td>
                            <td><span class="badge {{ ['paid'=>'text-bg-success','pending_payment'=>'text-bg-warning','partially_paid'=>'text-bg-info','partially_refunded'=>'text-bg-warning','refunded'=>'text-bg-secondary','cancelled'=>'text-bg-secondary'][$s->status] ?? 'text-bg-light' }}">{{ str_replace('_', ' ', $s->status) }}</span></td>
                            <td class="text-muted">{{ $s->created_at->format('d M Y H:i') }}</td>
                            <td class="text-end"><a href="{{ route('admin.sales.show', $s) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No sales yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($sales->hasPages())<div class="card-footer bg-white">{{ $sales->links() }}</div>@endif
    </div>
</x-app-layout>
