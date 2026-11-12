@php $cur = \App\Support\Settings::get('currency', 'TZS'); @endphp
<x-app-layout>
    <x-slot name="header">Invoice {{ $invoice->invoice_reference }}</x-slot>
    <x-slot name="headerActions">
        @if (! $invoice->isVoided() && (float) $invoice->balance > 0)
            <button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#paymentDrawer">
                <i class="hgi-stroke hgi-plus-sign me-1"></i> Record Payment
            </button>
        @endif
        <a href="{{ route('admin.invoices.preview', $invoice) }}" class="btn btn-outline-secondary btn-sm"><i class="hgi-stroke hgi-view me-1"></i> Preview</a>
        <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn btn-outline-secondary btn-sm"><i class="hgi-stroke hgi-download-01 me-1"></i> PDF</a>
        <a href="{{ route('admin.invoices.print', $invoice) }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="hgi-stroke hgi-printer me-1"></i> Print</a>
        @if (! $invoice->isVoided())
            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#voidModal">Void</button>
        @endif
    </x-slot>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Invoice Details</span>
                    <span class="badge-status status-{{ $invoice->status }}">{{ str_replace('_', ' ', $invoice->status) }}</span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-4 text-muted">Patient</dt>
                        <dd class="col-8"><a href="{{ route('patients.show', $invoice->patient) }}">{{ $invoice->patient?->full_name }}</a> ({{ $invoice->patient?->patient_number }})</dd>
                        <dt class="col-4 text-muted">Type</dt><dd class="col-8"><span class="badge text-bg-light border">{{ ucfirst($invoice->type ?? 'service') }}</span></dd>
                        <dt class="col-4 text-muted">Issue Date</dt><dd class="col-8">{{ $invoice->issue_date->format('d/m/Y') }}</dd>
                        <dt class="col-4 text-muted">Due Date</dt><dd class="col-8">{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '—' }}{{ $invoice->due_date && $invoice->due_date->isPast() && (float) $invoice->balance > 0 ? ' — overdue' : '' }}</dd>
                        <dt class="col-4 text-muted">Issued By</dt><dd class="col-8">{{ $invoice->issuer?->name ?? '—' }}</dd>
                        @if ($invoice->visit)
                            <dt class="col-4 text-muted">Visit</dt><dd class="col-8"><a href="{{ route('visits.show', $invoice->visit) }}">{{ $invoice->visit->visit_reference }}</a></dd>
                        @endif
                        @if ($invoice->notes)
                            <dt class="col-4 text-muted">Notes</dt><dd class="col-8" style="white-space: pre-wrap;">{{ $invoice->notes }}</dd>
                        @endif
                        @if ($invoice->void_reason)
                            <dt class="col-4 text-muted">Void Reason</dt><dd class="col-8 text-danger">{{ $invoice->void_reason }}</dd>
                        @endif
                    </dl>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Service</th><th class="text-end">Unit Price</th><th class="text-center">Qty</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td>{{ $item->service_name }}<div class="small text-muted">{{ $item->item_type === 'product' ? 'Product' : 'Service' }} · {{ $item->service_code }}</div></td>
                                    <td class="text-end">{{ number_format((float) $item->unit_price, 2) }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">{{ number_format((float) $item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-top">
                            <tr><td colspan="3" class="text-end text-muted">Subtotal</td><td class="text-end">{{ $cur }} {{ number_format((float) $invoice->subtotal, 2) }}</td></tr>
                            @if ((float) $invoice->discount_amount > 0)
                                <tr><td colspan="3" class="text-end text-muted">Discount ({{ $invoice->discount_reason }})</td><td class="text-end text-danger">- {{ $cur }} {{ number_format((float) $invoice->discount_amount, 2) }}</td></tr>
                            @endif
                            <tr><td colspan="3" class="text-end fw-semibold">Total</td><td class="text-end fw-bold">{{ $cur }} {{ number_format((float) $invoice->total, 2) }}</td></tr>
                            <tr><td colspan="3" class="text-end text-muted">Paid</td><td class="text-end text-success">{{ $cur }} {{ number_format((float) $invoice->amount_paid, 2) }}</td></tr>
                            <tr><td colspan="3" class="text-end fw-semibold">Balance</td><td class="text-end fw-bold {{ (float) $invoice->balance > 0 ? 'text-danger' : 'text-success' }}">{{ $cur }} {{ number_format((float) $invoice->balance, 2) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Payment History</div>
                <ul class="list-group list-group-flush">
                    @forelse ($invoice->payments as $payment)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="{{ route('admin.payments.show', $payment) }}">{{ $payment->payment_reference }}</a>
                                    <div class="small text-muted">{{ $payment->payment_date->format('d/m/Y') }} · {{ $payment->payment_method }} · {{ $payment->receiver?->name }}</div>
                                    @if ($payment->transaction_reference)<div class="small text-muted">Ref: {{ $payment->transaction_reference }}</div>@endif
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold">{{ $cur }} {{ number_format((float) $payment->amount, 2) }}</div>
                                    @if ($payment->status === 'voided' || (float) $payment->refundedAmount() > 0)
                                        <span class="small text-danger">{{ $payment->status === 'voided' ? 'Voided' : 'Refunded '.$cur.' '.number_format((float) $payment->refundedAmount(), 2) }}</span>
                                    @endif
                                    @if ($payment->verification_status === 'pending')
                                        <div class="mt-1">
                                            <span class="badge text-bg-warning">Awaiting verification</span>
                                            <form method="POST" action="{{ route('admin.payments.verify', $payment) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Verify</button></form>
                                            <form method="POST" action="{{ route('admin.payments.reject', $payment) }}" class="d-inline" onsubmit="return confirm('Reject this payment?')">@csrf<button class="btn btn-sm btn-outline-danger">Reject</button></form>
                                        </div>
                                    @elseif ($payment->verification_status === 'rejected')
                                        <span class="badge text-bg-danger">Rejected</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No payments recorded.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- Fulfilment tracking --}}
    @if ($invoice->serviceFulfilments->isNotEmpty() || $invoice->productFulfilments->isNotEmpty())
        <div class="row g-3 mt-1">
            @if ($invoice->serviceFulfilments->isNotEmpty())
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white fw-semibold"><i class="hgi-stroke hgi-stethoscope-02 me-1"></i>Service Fulfilment</div>
                        <ul class="list-group list-group-flush">
                            @foreach ($invoice->serviceFulfilments as $sf)
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold">{{ $sf->item?->service_name }}</div>
                                            <div class="small text-muted">
                                                {{ $sf->scheduled_date ? 'Scheduled '.$sf->scheduled_date->format('d/m/Y').' · ' : '' }}{{ $sf->assignee?->name ?? 'Unassigned' }}
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge text-bg-{{ $sf->status === 'completed' || $sf->status === 'closed' ? 'success' : ($sf->status === 'in_progress' ? 'info' : 'light border text-dark') }}">{{ str_replace('_', ' ', $sf->status) }}</span>
                                        </div>
                                    </div>
                                    <div class="mini-stepper mt-2">
                                        @foreach (\App\Models\ServiceFulfilment::STEPS as $i => $st)
                                            <span class="step-dot {{ $i < $sf->stepIndex() ? 'done' : ($i === $sf->stepIndex() ? 'current' : '') }}" title="{{ str_replace('_', ' ', $st) }}"></span>
                                        @endforeach
                                    </div>
                                    @if (! $invoice->isVoided() && $sf->status !== 'closed')
                                        <form method="POST" action="{{ route('admin.fulfilments.service-step', $sf) }}" class="d-flex gap-2 mt-2 align-items-center">
                                            @csrf
                                            @php $next = \App\Models\ServiceFulfilment::STEPS[$sf->stepIndex() + 1] ?? null; @endphp
                                            @if ($next)
                                                <input type="hidden" name="step" value="{{ $next }}">
                                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Note (optional)">
                                                <button class="btn btn-sm btn-outline-primary text-nowrap">→ {{ str_replace('_', ' ', $next) }}</button>
                                            @endif
                                        </form>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            @foreach ($invoice->productFulfilments as $pf)
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <span class="fw-semibold"><i class="hgi-stroke hgi-delivery-truck-01 me-1"></i>{{ $pf->tracking_reference }}</span>
                            <span class="badge text-bg-{{ $pf->status === 'completed' ? 'success' : 'info' }}">{{ str_replace('_', ' ', $pf->status) }}</span>
                        </div>
                        <div class="card-body">
                            <div class="mini-stepper mb-2">
                                @foreach (\App\Models\ProductFulfilment::STEPS as $i => $st)
                                    <span class="step-dot {{ $i < $pf->stepIndex() ? 'done' : ($i === $pf->stepIndex() ? 'current' : '') }}" title="{{ str_replace('_', ' ', $st) }}"></span>
                                @endforeach
                            </div>
                            <div class="small text-muted mb-2">
                                {{ $pf->delivery_method ? 'Method: '.$pf->delivery_method : '' }}{{ $pf->recipient_name ? ' · To: '.$pf->recipient_name : '' }}{{ $pf->courier ? ' · '.$pf->courier : '' }}
                            </div>
                            @if (! $invoice->isVoided() && $pf->status !== 'completed')
                                <form method="POST" action="{{ route('admin.fulfilments.product-step', $pf) }}" class="row g-2">
                                    @csrf
                                    @php $next = \App\Models\ProductFulfilment::STEPS[$pf->stepIndex() + 1] ?? null; @endphp
                                    @if ($next)
                                        <input type="hidden" name="step" value="{{ $next }}">
                                        @if (in_array($next, ['dispatched', 'delivered', 'ready_dispatch']))
                                            <div class="col-6"><select name="delivery_method" class="form-select form-select-sm"><option value="">Delivery method</option><option value="collection">Customer collection</option><option value="clinic_delivery">Clinic delivery</option><option value="courier">External courier</option><option value="other">Other</option></select></div>
                                            <div class="col-6"><input name="recipient_name" class="form-control form-control-sm" placeholder="Recipient name"></div>
                                        @endif
                                        @if ($next === 'delivered')
                                            <div class="col-12"><input name="proof_note" class="form-control form-control-sm" placeholder="Proof of delivery / signed by (required)" required></div>
                                        @endif
                                        <div class="col-8"><input name="notes" class="form-control form-control-sm" placeholder="Note (optional)"></div>
                                        <div class="col-4"><button class="btn btn-sm btn-outline-primary w-100 text-nowrap">→ {{ str_replace('_', ' ', $next) }}</button></div>
                                    @endif
                                </form>
                            @endif
                            @if ($pf->events->isNotEmpty())
                                <details class="mt-2"><summary class="small text-muted" style="cursor:pointer">History ({{ $pf->events->count() }})</summary>
                                    <ul class="list-unstyled small mt-1 mb-0">
                                        @foreach ($pf->events as $ev)
                                            <li>{{ $ev->created_at->format('d M H:i') }} — {{ str_replace('_', ' ', $ev->step) }}{{ $ev->notes ? ' · '.$ev->notes : '' }} <span class="text-muted">({{ $ev->user?->name }})</span></li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Record Payment Drawer --}}
    @if (! $invoice->isVoided() && (float) $invoice->balance > 0)
        <div class="offcanvas offcanvas-end drawer" tabindex="-1" id="paymentDrawer" data-bs-scroll="true">
            <div class="offcanvas-header border-bottom">
                <h6 class="offcanvas-title fw-semibold">Record Payment — {{ $invoice->invoice_reference }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <form method="POST" action="{{ route('admin.invoices.payments.store', $invoice) }}" class="d-flex flex-column h-100">
                @csrf
                <div class="offcanvas-body">
                    <div class="alert alert-light border small">Outstanding balance: <strong>{{ $cur }} {{ number_format((float) $invoice->balance, 2) }}</strong></div>
                    <div class="mb-3">
                        <label class="form-label">Amount ({{ $cur }}) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" value="{{ $invoice->balance }}" min="0.01" max="{{ $invoice->balance }}" step="0.01" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            @foreach ($paymentMethods as $method)
                                <option value="{{ $method }}">{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Transaction Reference</label>
                        <input type="text" name="transaction_reference" class="form-control" placeholder="Bank/mobile reference (Bank Transfer & Office Payment need verification)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Proof / Notes for Verification</label>
                        <input type="text" name="proof_note" class="form-control" placeholder="e.g. sender name, slip number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" value="{{ today()->toDateString() }}" class="form-control" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="2" class="form-control"></textarea>
                    </div>
                </div>
                <div class="border-top p-3 d-flex gap-2 bg-white sticky-bottom">
                    <button type="submit" class="btn btn-primary flex-grow-1">Record Payment</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Void Modal --}}
    <div class="modal fade" id="voidModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.invoices.void', $invoice) }}" class="modal-content">
                @csrf
                <div class="modal-header"><h6 class="modal-title">Void Invoice {{ $invoice->invoice_reference }}</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <label class="form-label">Void reason <span class="text-danger">*</span></label>
                    <textarea name="void_reason" rows="2" class="form-control" required></textarea>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-danger">Void Invoice</button></div>
            </form>
        </div>
    </div>
</x-app-layout>
