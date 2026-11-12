@php $cur = \App\Support\Settings::get('currency', 'TZS'); @endphp
<x-app-layout>
    <x-slot name="header">{{ __('Payments') }}</x-slot>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4"><input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search reference, invoice or patient..."></div>
                <div class="col-auto">
                    <select name="method" class="form-select form-select-sm">
                        <option value="">All methods</option>
                        @foreach ($paymentMethods as $method)
                            <option value="{{ $method }}" @selected(request('method') === $method)>{{ $method }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto"><input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm"></div>
                <div class="col-auto"><input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm"></div>
                <div class="col-auto"><button class="btn btn-outline-secondary btn-sm">Filter</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Reference</th><th>Invoice</th><th>Patient</th><th>Date</th><th>Method</th><th class="text-end">Amount</th><th>Received By</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td><a href="{{ route('admin.payments.show', $payment) }}">{{ $payment->payment_reference }}</a></td>
                            <td><a href="{{ route('admin.invoices.show', $payment->invoice) }}">{{ $payment->invoice?->invoice_reference }}</a></td>
                            <td>{{ $payment->patient?->full_name }}</td>
                            <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td>{{ $payment->payment_method }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $payment->amount, 2) }}</td>
                            <td>{{ $payment->receiver?->name }}</td>
                            <td><span class="badge-status status-{{ $payment->status === 'completed' ? 'paid' : 'voided' }}">{{ $payment->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No payments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($payments->hasPages())<div class="card-footer bg-white">{{ $payments->links() }}</div>@endif
    </div>
</x-app-layout>
