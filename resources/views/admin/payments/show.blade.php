@php $cur = \App\Support\Settings::get('currency', 'TZS'); @endphp
<x-app-layout>
    <x-slot name="header">Receipt {{ $payment->payment_reference }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('admin.payments.print', $payment) }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="hgi-stroke hgi-printer me-1"></i> Print Receipt</a>
        @if ($payment->status === 'completed' && (float) $payment->refundableAmount() > 0)
            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#refundModal">Refund</button>
        @endif
    </x-slot>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Payment Receipt</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-4 text-muted">Reference</dt><dd class="col-8">{{ $payment->payment_reference }}</dd>
                        <dt class="col-4 text-muted">Invoice</dt><dd class="col-8"><a href="{{ route('admin.invoices.show', $payment->invoice) }}">{{ $payment->invoice?->invoice_reference }}</a></dd>
                        <dt class="col-4 text-muted">Patient</dt><dd class="col-8">{{ $payment->patient?->full_name }} ({{ $payment->patient?->patient_number }})</dd>
                        <dt class="col-4 text-muted">Amount</dt><dd class="col-8 fw-bold fs-6">{{ $cur }} {{ number_format((float) $payment->amount, 2) }}</dd>
                        <dt class="col-4 text-muted">Method</dt><dd class="col-8">{{ $payment->payment_method }}</dd>
                        <dt class="col-4 text-muted">Date</dt><dd class="col-8">{{ $payment->payment_date->format('d/m/Y') }}</dd>
                        <dt class="col-4 text-muted">Received By</dt><dd class="col-8">{{ $payment->receiver?->name ?? '—' }}</dd>
                        <dt class="col-4 text-muted">Status</dt><dd class="col-8"><span class="badge-status status-{{ $payment->status === 'completed' ? 'paid' : 'voided' }}">{{ $payment->status }}</span></dd>
                        @if ($payment->notes)
                            <dt class="col-4 text-muted">Notes</dt><dd class="col-8">{{ $payment->notes }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Refunds</div>
                <ul class="list-group list-group-flush">
                    @forelse ($payment->refunds as $refund)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <span class="fw-semibold">{{ $refund->refund_reference }}</span>
                                    <div class="small text-muted">{{ $refund->reason }} · {{ $refund->refunder?->name }} · {{ $refund->created_at->format('d/m/Y') }}</div>
                                </div>
                                <span class="text-danger fw-semibold">- {{ $cur }} {{ number_format((float) $refund->amount, 2) }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No refunds.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    @if ($payment->status === 'completed' && (float) $payment->refundableAmount() > 0)
        <div class="modal fade" id="refundModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('admin.payments.refund', $payment) }}" class="modal-content">
                    @csrf
                    <div class="modal-header"><h6 class="modal-title">Refund {{ $payment->payment_reference }}</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <p class="small text-muted">Refundable: <strong>{{ $cur }} {{ number_format((float) $payment->refundableAmount(), 2) }}</strong></p>
                        <div class="mb-3">
                            <label class="form-label">Refund amount ({{ $cur }}) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" value="{{ $payment->refundableAmount() }}" min="0.01" max="{{ $payment->refundableAmount() }}" step="0.01" class="form-control" required>
                        </div>
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="2" class="form-control" required></textarea>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-danger">Record Refund</button></div>
                </form>
            </div>
        </div>
    @endif
</x-app-layout>
