@php $labels = ['received'=>'Received','payment_verified'=>'Payment Verified','stock_confirmed'=>'Stock Confirmed','picking'=>'Picking','quality_check'=>'Quality Check','packaging'=>'Packaging','ready_dispatch'=>'Ready','dispatched'=>'Dispatched','delivered'=>'Delivered','completed'=>'Completed']; @endphp
<x-app-layout>
    <x-slot name="header">{{ __('Delivery Tracking') }}</x-slot>
    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2">
                <div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Tracking ref or recipient..."></div>
                <div class="col-md-4"><select name="status" class="form-select"><option value="">All statuses</option>@foreach ($steps as $st)<option value="{{ $st }}" @selected(request('status') === $st)>{{ $labels[$st] }}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th>Tracking</th><th>Source</th><th>Customer</th><th style="width:28%">Progress</th><th>Status</th><th>Method</th><th></th></tr></thead>
                <tbody>
                    @forelse ($fulfilments as $f)
                        <tr>
                            <td class="fw-semibold">{{ $f->tracking_reference }}</td>
                            <td class="text-muted">{{ $f->invoice?->invoice_reference ?? $f->sale?->sale_reference }}</td>
                            <td>{{ $f->patient?->full_name ?? $f->customer?->name ?? $f->recipient_name ?? '—' }}</td>
                            <td><div class="mini-stepper">@foreach ($steps as $i => $st)<span class="step-dot {{ $i < $f->stepIndex() ? 'done' : ($i === $f->stepIndex() ? 'current' : '') }}" title="{{ $labels[$st] }}"></span>@endforeach</div></td>
                            <td><span class="badge text-bg-{{ $f->status === 'completed' ? 'success' : 'info' }}">{{ $labels[$f->status] }}</span></td>
                            <td class="text-muted">{{ $f->delivery_method ?? '—' }}</td>
                            <td class="text-end"><a href="{{ $f->invoice ? route('admin.invoices.show', $f->invoice) : route('admin.sales.show', $f->sale) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No fulfilment orders.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($fulfilments->hasPages())<div class="card-footer bg-white">{{ $fulfilments->links() }}</div>@endif
    </div>
</x-app-layout>
