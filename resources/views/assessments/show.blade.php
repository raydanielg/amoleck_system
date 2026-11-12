<x-app-layout>
    <x-slot name="header">{{ __('Assessment '.$assessment->reference) }}</x-slot>
    <x-slot name="headerActions">
        <a href="{{ route('assessments.print',$assessment) }}" target="_blank" class="btn btn-primary btn-sm"><i class="hgi-stroke hgi-printer me-1"></i>Print</a>
        @if (auth()->user()->isAdmin() || auth()->user()->hasPermission('clinical.assess'))
            <a href="{{ route('assessments.edit',$assessment) }}" class="btn btn-outline-primary btn-sm">Edit</a>
        @endif
        <a href="{{ route('assessments.index',$assessment->patient) }}" class="btn btn-outline-secondary btn-sm">All assessments</a>
    </x-slot>

    <div class="row justify-content-center"><div class="col-lg-10">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-0">{{ $assessment->patient->full_name }} <span class="text-muted fw-normal small">({{ $assessment->patient->patient_number }})</span></h5>
                        <div class="small text-muted">{{ ucfirst($assessment->patient->sex ?? '—') }} · {{ $assessment->patient->date_of_birth?->age ?? '—' }} yrs · {{ $assessment->patient->occupation ?? '—' }}</div>
                    </div>
                    <div class="text-end small text-muted">
                        {{ $assessment->assessed_at->format('d M Y H:i') }}<br>by {{ $assessment->assessor->name }}
                        @if ($assessment->visit)<br>{{ $assessment->visit->visit_reference }}@endif
                    </div>
                </div>

                @php $n = 2; @endphp
                <h6 class="text-uppercase small fw-bold text-muted border-bottom pb-1">History</h6>
                @foreach (\App\Models\PtAssessment::SECTIONS['history'] as $field => $label)
                    @if ($assessment->{$field})
                        <div class="mb-3"><div class="small fw-semibold">{{ $n }}. {{ $label }}</div><div class="small" style="white-space:pre-wrap">{{ $assessment->{$field} }}</div></div>
                    @endif @php $n++; @endphp
                @endforeach

                <h6 class="text-uppercase small fw-bold text-muted border-bottom pb-1 mt-4">Examination</h6>
                @if ($assessment->vitals && array_filter($assessment->vitals))
                    <div class="mb-3"><div class="small fw-semibold">10. Vitals</div>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            @foreach (['bp'=>'BP','hr'=>'HR','rr'=>'RR','temp'=>'Temp','spo2'=>'SpO₂','weight'=>'Weight','height'=>'Height'] as $v => $lbl)
                                @if (!empty($assessment->vitals[$v]))<span class="badge text-bg-light border">{{ $lbl }}: {{ $assessment->vitals[$v] }}</span>@endif
                            @endforeach
                        </div>
                    </div>
                @endif
                @php $n = 9; @endphp
                @foreach (\App\Models\PtAssessment::SECTIONS['examination'] as $field => $label)
                    @if ($assessment->{$field})
                        <div class="mb-3"><div class="small fw-semibold">{{ $n }}. {{ $label }}</div><div class="small" style="white-space:pre-wrap">{{ $assessment->{$field} }}</div></div>
                    @endif @php $n++; @endphp
                @endforeach

                <h6 class="text-uppercase small fw-bold text-muted border-bottom pb-1 mt-4">Treatment Plan</h6>
                @php $n = 16; @endphp
                @foreach (\App\Models\PtAssessment::SECTIONS['plan'] as $field => $label)
                    @if ($assessment->{$field})
                        <div class="mb-3"><div class="small fw-semibold">{{ $n }}. {{ $label }}</div><div class="small" style="white-space:pre-wrap">{{ $assessment->{$field} }}</div></div>
                    @endif @php $n++; @endphp
                @endforeach
                @if ($assessment->home_visit)
                    <div class="mb-3"><div class="small fw-semibold">20. Home Visit</div><div class="small"><span class="badge text-bg-info">Recommended</span> {{ $assessment->home_visit_notes }}</div></div>
                @endif
            </div>
        </div>
    </div></div>
</x-app-layout>
