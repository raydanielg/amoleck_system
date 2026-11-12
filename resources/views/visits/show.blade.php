<x-app-layout>
    <x-slot name="header">Visit {{ $visit->visit_reference }}</x-slot>
    <x-slot name="headerActions">
        <div class="d-flex gap-2">
            @if ($visit->isDraft() && $canEdit)
                <form method="POST" action="{{ route('visits.complete', $visit) }}">
                    @csrf
                    <button class="btn btn-success btn-sm"><i class="hgi-stroke hgi-checkmark-circle-02 me-1"></i>Complete Visit</button>
                </form>
            @endif
            @if ($canEdit && ! $visit->isVoided())
                <a href="{{ route('visits.edit', $visit) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
            @endif
            @if (Auth::user()->isAdmin() && ! $visit->isVoided() && \App\Support\Settings::bool('billing_enabled', true))
                <form method="POST" action="{{ route('admin.invoices.from-visit', $visit) }}">
                    @csrf
                    <button class="btn btn-outline-primary btn-sm"><i class="hgi-stroke hgi-invoice-01 me-1"></i>Invoice</button>
                </form>
            @endif
            <a href="{{ route('visits.print', $visit) }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="hgi-stroke hgi-printer me-1"></i>Print</a>
        </div>
    </x-slot>

    @if ($visit->isVoided())
        <div class="alert alert-secondary"><strong>Voided.</strong> {{ $visit->void_reason }} — by {{ $visit->voider?->name }} on {{ $visit->voided_at?->format('d/m/Y H:i') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-semibold">Visit Details</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Reference</dt><dd class="col-7">{{ $visit->visit_reference }}</dd>
                        <dt class="col-5 text-muted">Patient</dt>
                        <dd class="col-7"><a href="{{ route('patients.show', $visit->patient) }}">{{ $visit->patient?->full_name }}</a> ({{ $visit->patient?->patient_number }})</dd>
                        <dt class="col-5 text-muted">Date & Time</dt><dd class="col-7">{{ $visit->visit_datetime->format('d/m/Y H:i') }}</dd>
                        <dt class="col-5 text-muted">Status</dt>
                        <dd class="col-7"><span class="badge {{ ['completed' => 'text-bg-success', 'draft' => 'text-bg-warning', 'voided' => 'text-bg-secondary'][$visit->status] ?? 'text-bg-light' }}">{{ ucfirst($visit->status) }}</span></dd>
                        <dt class="col-5 text-muted">Follow-up</dt><dd class="col-7">{{ $visit->follow_up_date?->format('d/m/Y') ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Recorded By</dt><dd class="col-7">{{ $visit->recorder?->name ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-3">
                <div class="card-header bg-white fw-semibold">Services Provided</div>
                <ul class="list-group list-group-flush">
                    @forelse ($visit->services as $vs)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ $vs->service_name_snapshot }}</span>
                            @if ($vs->price_snapshot)<span class="text-muted small">{{ number_format((float) $vs->price_snapshot) }}</span>@endif
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No services recorded.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-lg-7">
            @foreach ([
                'Chief Complaint' => $visit->chief_complaint,
                'Assessment / Findings' => $visit->assessment_notes,
                'Treatment Notes' => $visit->treatment_notes,
                'Plan / Advice' => $visit->plan_notes,
            ] as $label => $value)
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white fw-semibold">{{ $label }}</div>
                    <div class="card-body small" style="white-space: pre-wrap;">{{ $value ?: '—' }}</div>
                </div>
            @endforeach

            @if (Auth::user()->isAdmin() && ! $visit->isVoided())
                <div class="card shadow-sm border-0 border-danger">
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.visits.void', $visit) }}" onsubmit="return confirm('Void this visit? A reason is required and will be logged.');">
                            @csrf
                            <label class="form-label small text-danger fw-semibold">Void visit (reason required)</label>
                            <div class="input-group">
                                <input type="text" name="void_reason" class="form-control form-control-sm" required placeholder="Reason for voiding">
                                <button class="btn btn-sm btn-outline-danger">Void</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
