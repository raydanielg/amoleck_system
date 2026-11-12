@php $cur = \App\Support\Settings::get('currency', 'TZS'); @endphp
<x-app-layout>
    <x-slot name="header">{{ __('Refunds') }}</x-slot>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Reference</th><th>Payment</th><th>Patient</th><th class="text-end">Amount</th><th>Reason</th><th>By</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($refunds as $refund)
                        <tr>
                            <td>{{ $refund->refund_reference }}</td>
                            <td><a href="{{ route('admin.payments.show', $refund->payment) }}">{{ $refund->payment?->payment_reference }}</a></td>
                            <td>{{ $refund->payment?->patient?->full_name }}</td>
                            <td class="text-end text-danger fw-semibold">- {{ number_format((float) $refund->amount, 2) }}</td>
                            <td class="small">{{ $refund->reason }}</td>
                            <td>{{ $refund->refunder?->name }}</td>
                            <td>{{ $refund->created_at->format('d/m/Y') }}</td>
                            <td><span class="badge-status status-{{ $refund->status === 'refunded' ? 'rejected' : 'voided' }}">{{ $refund->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No refunds recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($refunds->hasPages())<div class="card-footer bg-white">{{ $refunds->links() }}</div>@endif
    </div>
</x-app-layout>
