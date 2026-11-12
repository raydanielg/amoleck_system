@php $requireNotes = \App\Support\Settings::bool('visit_require_notes'); @endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Visit Date & Time <span class="text-danger">*</span></label>
        <input type="datetime-local" name="visit_datetime"
               value="{{ old('visit_datetime', isset($visit) ? $visit->visit_datetime->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
               class="form-control @error('visit_datetime') is-invalid @enderror" required>
        @error('visit_datetime')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label">Chief Complaint</label>
        <textarea name="chief_complaint" rows="2" class="form-control @error('chief_complaint') is-invalid @enderror" placeholder="Patient's reason for attendance">{{ old('chief_complaint', $visit->chief_complaint ?? '') }}</textarea>
        @error('chief_complaint')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label">Assessment / Findings</label>
        <textarea name="assessment_notes" rows="3" class="form-control @error('assessment_notes') is-invalid @enderror">{{ old('assessment_notes', $visit->assessment_notes ?? '') }}</textarea>
        @error('assessment_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label">Services Provided @if(\App\Support\Settings::bool('visit_require_service', true))<span class="text-danger">*</span>@endif</label>
        <div class="border rounded p-3 bg-white @error('services') border-danger @enderror">
            @php $selected = collect(old('services', isset($visit) ? $visit->services->pluck('service_id')->all() : [])); @endphp
            @forelse ($services->groupBy(fn ($s) => $s->category?->name ?? 'Other') as $category => $group)
                <div class="small fw-semibold text-muted mb-1">{{ $category }}</div>
                <div class="row mb-2">
                    @foreach ($group as $service)
                        <div class="col-md-6 col-lg-4">
                            <div class="form-check">
                                @if ($multipleServices)
                                    <input type="checkbox" name="services[]" value="{{ $service->id }}" id="svc{{ $service->id }}" class="form-check-input" @checked($selected->contains($service->id))>
                                @else
                                    <input type="radio" name="services[]" value="{{ $service->id }}" id="svc{{ $service->id }}" class="form-check-input" @checked($selected->contains($service->id))>
                                @endif
                                <label class="form-check-label" for="svc{{ $service->id }}">
                                    {{ $service->name }}
                                    @if ($service->standard_price)
                                        <span class="text-muted small">— {{ number_format((float) $service->standard_price) }}</span>
                                    @endif
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="text-muted">No active services. The Administrator must configure services first.</div>
            @endforelse
        </div>
        @error('services')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label">Treatment Notes @if($requireNotes)<span class="text-danger">*</span>@endif</label>
        <textarea name="treatment_notes" rows="3" class="form-control @error('treatment_notes') is-invalid @enderror" placeholder="What was performed during this session">{{ old('treatment_notes', $visit->treatment_notes ?? '') }}" @if($requireNotes) required @endif></textarea>
        @error('treatment_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-8">
        <label class="form-label">Plan / Advice</label>
        <textarea name="plan_notes" rows="2" class="form-control @error('plan_notes') is-invalid @enderror" placeholder="Follow-up advice or next steps">{{ old('plan_notes', $visit->plan_notes ?? '') }}</textarea>
        @error('plan_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Follow-up Date</label>
        <input type="date" name="follow_up_date" value="{{ old('follow_up_date', isset($visit) && $visit->follow_up_date ? $visit->follow_up_date->format('Y-m-d') : '') }}" class="form-control @error('follow_up_date') is-invalid @enderror">
        @error('follow_up_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
