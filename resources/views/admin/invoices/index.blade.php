@php $cur = \App\Support\Settings::get('currency', 'TZS'); @endphp
<x-app-layout>
    <x-slot name="header">{{ __('Invoices') }}</x-slot>
    <x-slot name="headerActions">
        @if ($billingEnabled)
            <button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#invoiceDrawer">
                <i class="hgi-stroke hgi-plus-sign me-1"></i> New Invoice
            </button>
        @endif
    </x-slot>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4"><input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search reference or patient..."></div>
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All statuses</option>
                        @foreach (['issued' => 'Issued', 'partially_paid' => 'Partially Paid', 'paid' => 'Paid', 'voided' => 'Voided'] as $v => $l)
                            <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
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
                <thead><tr><th>Reference</th><th>Patient</th><th>Date</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td><a href="{{ route('admin.invoices.show', $invoice) }}">{{ $invoice->invoice_reference }}</a></td>
                            <td>{{ $invoice->patient?->full_name }}<div class="small text-muted">{{ $invoice->patient?->patient_number }}</div></td>
                            <td>{{ $invoice->issue_date->format('d/m/Y') }}</td>
                            <td class="text-end">{{ number_format((float) $invoice->total, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $invoice->amount_paid, 2) }}</td>
                            <td class="text-end {{ (float) $invoice->balance > 0 ? 'text-danger fw-semibold' : '' }}">{{ number_format((float) $invoice->balance, 2) }}</td>
                            <td><span class="badge-status status-{{ $invoice->status }}">{{ str_replace('_', ' ', $invoice->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No invoices found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($invoices->hasPages())<div class="card-footer bg-white">{{ $invoices->links() }}</div>@endif
    </div>

    {{-- New Invoice Drawer --}}
    <div class="offcanvas offcanvas-end drawer" tabindex="-1" id="invoiceDrawer" data-bs-scroll="true">
        <div class="offcanvas-header border-bottom">
            <h6 class="offcanvas-title fw-semibold">New Invoice</h6>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <form method="POST" action="{{ route('admin.invoices.store') }}" class="d-flex flex-column h-100">
            @csrf
            <div class="offcanvas-body">
                <div class="mb-3">
                    <label class="form-label">Patient <span class="text-danger">*</span></label>
                    <select name="patient_id" class="form-select" required>
                        <option value="">Select patient...</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}">{{ $patient->full_name }} ({{ $patient->patient_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                        <input type="date" name="issue_date" value="{{ today()->toDateString() }}" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Physiotherapy Services</label>
                    <div class="border rounded p-2" style="max-height: 300px; overflow-y: auto;">
                        @foreach ($services as $service)
                            <div class="d-flex align-items-center gap-2 py-1 border-bottom">
                                <div class="form-check flex-grow-1 mb-0">
                                    <input type="checkbox" name="services[]" value="{{ $service->id }}" class="form-check-input" id="svc{{ $service->id }}">
                                    <label class="form-check-label small" for="svc{{ $service->id }}">
                                        {{ $service->name }} <span class="text-muted">· {{ $cur }} {{ number_format((float) $service->standard_price) }}</span>
                                    </label>
                                </div>
                                <input type="number" name="quantities[{{ $service->id }}]" value="1" min="1" class="form-control form-control-sm" style="width: 60px;" title="Quantity">
                            </div>
                        @endforeach
                    </div>
                </div>
                @if (isset($products) && $products->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label">Medical Products</label>
                        <div class="border rounded p-2" style="max-height: 220px; overflow-y: auto;">
                            @foreach ($products as $product)
                                <div class="d-flex align-items-center gap-2 py-1 border-bottom">
                                    <div class="form-check flex-grow-1 mb-0">
                                        <input type="checkbox" name="products[]" value="{{ $product->id }}" class="form-check-input" id="prd{{ $product->id }}" @disabled($product->stock_quantity <= 0)>
                                        <label class="form-check-label small" for="prd{{ $product->id }}">
                                            {{ $product->name }} <span class="text-muted">· {{ $cur }} {{ number_format((float) $product->selling_price) }} · {{ $product->stock_quantity }} in stock</span>
                                        </label>
                                    </div>
                                    <input type="number" name="product_quantities[{{ $product->id }}]" value="1" min="1" max="{{ $product->stock_quantity }}" class="form-control form-control-sm" style="width: 60px;" title="Quantity">
                                </div>
                            @endforeach
                        </div>
                        <div class="form-text">Stock is reserved when the invoice is created.</div>
                    </div>
                @endif
                @if (\App\Support\Settings::bool('allow_discounts', true))
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Discount ({{ $cur }})</label>
                            <input type="number" name="discount_amount" value="0" min="0" step="0.01" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Discount Reason</label>
                            <input type="text" name="discount_reason" class="form-control" placeholder="Required if discount">
                        </div>
                    </div>
                @endif
                <div class="mb-0">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="2" class="form-control"></textarea>
                </div>
            </div>
            <div class="border-top p-3 d-flex gap-2 bg-white sticky-bottom">
                <button type="submit" class="btn btn-primary flex-grow-1">Create Invoice</button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">Cancel</button>
            </div>
        </form>
    </div>
</x-app-layout>
