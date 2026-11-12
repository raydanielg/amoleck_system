<x-app-layout>
    <x-slot name="header">{{ $patient->full_name }} <span class="text-muted fs-6 fw-normal">({{ $patient->patient_number }})</span></x-slot>
    <x-slot name="headerActions">
        <div class="d-flex gap-2">
            @if (! $patient->isArchived())
                <a href="{{ route('patients.visits.create', $patient) }}" class="btn btn-primary btn-sm">
                    <i class="hgi-stroke hgi-plus-sign me-1"></i>{{ __('New Visit') }}
                </a>
                <a href="{{ route('patients.edit', $patient) }}" class="btn btn-outline-secondary btn-sm">{{ __('Edit') }}</a>
            @endif
            <a href="{{ route('patients.print', $patient) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="hgi-stroke hgi-printer me-1"></i>{{ __('Print') }}
            </a>
        </div>
    </x-slot>

    @if ($patient->isArchived())
        <div class="alert alert-secondary">
            <strong>Archived.</strong> {{ $patient->archive_reason }}
            @if (Auth::user()->isAdmin())
                <form method="POST" action="{{ route('admin.patients.restore', $patient) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-outline-dark ms-2">Restore</button>
                </form>
            @endif
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Patient Details</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Patient No</dt><dd class="col-7">{{ $patient->patient_number }}</dd>
                        <dt class="col-5 text-muted">Full Name</dt><dd class="col-7">{{ $patient->full_name }}</dd>
                        <dt class="col-5 text-muted">Sex</dt><dd class="col-7">{{ ucfirst($patient->sex ?? '—') }}</dd>
                        <dt class="col-5 text-muted">Date of Birth</dt><dd class="col-7">{{ $patient->date_of_birth?->format('d/m/Y') ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Age</dt><dd class="col-7">{{ $patient->age ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Phone</dt><dd class="col-7">{{ $patient->phone ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Address</dt><dd class="col-7">{{ $patient->address ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Registered</dt><dd class="col-7">{{ $patient->created_at->format('d/m/Y') }}</dd>
                        <dt class="col-5 text-muted">Registered By</dt><dd class="col-7">{{ $patient->creator?->name ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Status</dt>
                        <dd class="col-7"><span class="badge {{ $patient->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($patient->status) }}</span></dd>
                    </dl>
                </div>
            </div>

            @if ($patient->emergency_contact_name)
                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-header bg-white fw-semibold">Emergency Contact</div>
                    <div class="card-body small">
                        {{ $patient->emergency_contact_name }}
                        @if ($patient->emergency_contact_relationship) ({{ $patient->emergency_contact_relationship }})@endif
                        @if ($patient->emergency_contact_phone)<div class="text-muted">{{ $patient->emergency_contact_phone }}</div>@endif
                    </div>
                </div>
            @endif

            @if ($patient->medical_history)
                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-header bg-white fw-semibold">Medical History</div>
                    <div class="card-body small">{{ $patient->medical_history }}</div>
                </div>
            @endif

            @if (Auth::user()->isAdmin() && ! $patient->isArchived())
                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.patients.archive', $patient) }}" onsubmit="return confirm('Archive this patient?');">
                            @csrf
                            <label class="form-label small text-muted">Archive reason</label>
                            <div class="input-group">
                                <input type="text" name="archive_reason" class="form-control form-control-sm" required placeholder="Reason for archiving">
                                <button class="btn btn-sm btn-outline-danger">Archive</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Visit History ({{ $patient->visits->count() }})</div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Reference</th><th>Date</th><th>Services</th><th>Follow-up</th><th>Status</th><th></th></tr>
                        </thead>
                        <tbody>
                            @forelse ($patient->visits as $visit)
                                <tr>
                                    <td class="fw-semibold">{{ $visit->visit_reference }}</td>
                                    <td>{{ $visit->visit_datetime->format('d/m/Y H:i') }}</td>
                                    <td class="small">{{ $visit->services->pluck('service_name_snapshot')->implode(', ') ?: '—' }}</td>
                                    <td>{{ $visit->follow_up_date?->format('d/m/Y') ?? '—' }}</td>
                                    <td>
                                        <span class="badge {{ ['completed' => 'text-bg-success', 'draft' => 'text-bg-warning', 'voided' => 'text-bg-secondary'][$visit->status] ?? 'text-bg-light' }}">
                                            {{ ucfirst($visit->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end"><a href="{{ route('visits.show', $visit) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No visits recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if (Auth::user()->isAdmin() && \App\Support\Settings::bool('billing_enabled', true))
                @php $cur = \App\Support\Settings::get('currency', 'TZS'); @endphp
                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                        <span>Billing History</span>
                        @php $outstanding = $patient->outstandingBalance(); @endphp
                        @if ((float) $outstanding > 0)
                            <span class="badge-status status-partially_paid">Outstanding: {{ $cur }} {{ number_format((float) $outstanding, 2) }}</span>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light"><tr><th>Invoice</th><th>Date</th><th class="text-end">Total</th><th class="text-end">Balance</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                @forelse ($patient->invoices as $invoice)
                                    <tr>
                                        <td class="fw-semibold">{{ $invoice->invoice_reference }}</td>
                                        <td>{{ $invoice->issue_date->format('d/m/Y') }}</td>
                                        <td class="text-end">{{ number_format((float) $invoice->total, 2) }}</td>
                                        <td class="text-end {{ (float) $invoice->balance > 0 ? 'text-danger' : '' }}">{{ number_format((float) $invoice->balance, 2) }}</td>
                                        <td><span class="badge-status status-{{ $invoice->status }}">{{ str_replace('_', ' ', $invoice->status) }}</span></td>
                                        <td class="text-end"><a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">No invoices.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($patient->followUps->isNotEmpty())
                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-header bg-white fw-semibold">Follow-ups</div>
                    <ul class="list-group list-group-flush">
                        @foreach ($patient->followUps as $followUp)
                            <li class="list-group-item d-flex justify-content-between align-items-center small">
                                <span>{{ $followUp->due_date->format('d/m/Y') }} — {{ $followUp->notes }}</span>
                                <span class="badge-status status-{{ $followUp->status === 'returned' ? 'paid' : $followUp->status }}">{{ $followUp->status }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
