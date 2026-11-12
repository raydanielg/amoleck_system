<x-app-layout>
    <x-slot name="header">{{ $assessment->exists ? 'Edit Assessment' : 'Physiotherapy Assessment' }} — {{ $patient->full_name }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('patients.show',$patient) }}" class="btn btn-outline-secondary btn-sm">Back to patient</a>
    </x-slot>

    <form method="POST" action="{{ $assessment->exists ? route('assessments.update',$assessment) : route('assessments.store',$patient) }}">
        @csrf
        @if ($assessment->exists) @method('PATCH') @endif
        @if ($visit)<input type="hidden" name="visit_id" value="{{ $visit->id }}">@endif

        {{-- 1. Anamnesis (from patient record) --}}
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-semibold">1. Anamnesis</div>
            <div class="card-body">
                <div class="row g-3 small">
                    @php $age = $patient->date_of_birth ? $patient->date_of_birth->age.' yrs' : '—'; @endphp
                    @foreach ([
                        'Name' => $patient->full_name, 'Age' => $age, 'Sex' => ucfirst($patient->sex ?? '—'),
                        'Phone' => $patient->phone ?? '—', 'Address' => $patient->address ?? '—',
                        'Occupation' => $patient->occupation ?? '—', 'Religion' => $patient->religion ?? '—',
                        'Tribe' => $patient->tribe ?? '—',
                    ] as $label => $value)
                        <div class="col-md-3"><div class="text-muted">{{ $label }}</div><div class="fw-semibold">{{ $value }}</div></div>
                    @endforeach
                </div>
                @if (! $patient->occupation || ! $patient->religion || ! $patient->tribe)
                    <div class="alert alert-light border small mt-3 mb-0 py-2">
                        Demographics incomplete — <a href="{{ route('patients.edit',$patient) }}">update patient record</a> to add occupation, religion or tribe.
                    </div>
                @endif
                @if ($visit)<div class="small text-muted mt-2">Linked to visit <strong>{{ $visit->visit_reference }}</strong> ({{ $visit->visit_datetime?->format('d M Y H:i') }})</div>@endif
            </div>
        </div>

        {{-- History --}}
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-semibold">History</div>
            <div class="card-body row g-3">
                @php $n = 2; @endphp
                @foreach (\App\Models\PtAssessment::SECTIONS['history'] as $field => $label)
                    <div class="col-md-6">
                        <label class="form-label">{{ $n++ }}. {{ $label }}</label>
                        <textarea name="{{ $field }}" rows="3" class="form-control">{{ old($field, $assessment->{$field}) }}</textarea>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Examination --}}
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-semibold">Examination</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">9. Observation</label>
                        <textarea name="observation" rows="3" class="form-control">{{ old('observation', $assessment->observation) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">10. Vitals</label>
                        <div class="row g-2">
                            @foreach (['bp'=>'BP (mmHg)','hr'=>'HR (bpm)','rr'=>'RR (/min)','temp'=>'Temp (°C)','spo2'=>'SpO₂ (%)','weight'=>'Weight (kg)','height'=>'Height (cm)'] as $v => $lbl)
                                <div class="col-6">
                                    <div class="input-group input-group-sm"><span class="input-group-text" style="min-width:110px">{{ $lbl }}</span>
                                        <input name="vitals[{{ $v }}]" value="{{ old('vitals.'.$v, $assessment->vitals[$v] ?? '') }}" class="form-control">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @php $n = 11; @endphp
                    @foreach (\App\Models\PtAssessment::SECTIONS['examination'] as $field => $label)
                        @if ($field === 'observation') @continue @endif
                        <div class="col-md-6">
                            <label class="form-label">{{ $n++ }}. {{ $label }}</label>
                            <textarea name="{{ $field }}" rows="3" class="form-control">{{ old($field, $assessment->{$field}) }}</textarea>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Treatment plan --}}
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-semibold">Treatment Plan</div>
            <div class="card-body row g-3">
                @php $n = 16; @endphp
                @foreach (\App\Models\PtAssessment::SECTIONS['plan'] as $field => $label)
                    <div class="col-md-6">
                        <label class="form-label">{{ $n++ }}. {{ $label }}</label>
                        <textarea name="{{ $field }}" rows="3" class="form-control">{{ old($field, $assessment->{$field}) }}</textarea>
                    </div>
                @endforeach
                <div class="col-md-6">
                    <label class="form-label">20. Home Visit</label>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="home_visit" value="1" id="hv" {{ old('home_visit', $assessment->home_visit) ? 'checked' : '' }}>
                        <label class="form-check-label" for="hv">Home-based physiotherapy recommended</label>
                    </div>
                    <textarea name="home_visit_notes" rows="2" class="form-control" placeholder="Home visit schedule, instructions…">{{ old('home_visit_notes', $assessment->home_visit_notes) }}</textarea>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('patients.show',$patient) }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary btn-arrow"><span class="btn-arrow-label">{{ $assessment->exists ? 'Update Assessment' : 'Save Assessment' }}</span><span class="btn-arrow-tile"><i class="hgi-stroke hgi-arrow-right-01"></i></span></button>
        </div>
    </form>
</x-app-layout>
