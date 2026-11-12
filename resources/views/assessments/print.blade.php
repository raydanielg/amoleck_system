<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>PT Assessment {{ $assessment->reference }}</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: "Segoe UI", system-ui, sans-serif; color:#1a1a1a; font-size:12px; margin:0; padding:24px; }
    .head { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #02481f; padding-bottom:10px; margin-bottom:14px; }
    .head h1 { font-size:16px; margin:0; color:#02481f; }
    .head .sub { color:#666; font-size:11px; margin-top:2px; }
    .meta { text-align:right; font-size:11px; color:#555; }
    table.dem { width:100%; border-collapse:collapse; margin-bottom:12px; }
    table.dem td { padding:3px 6px; font-size:11px; }
    table.dem .lbl { color:#777; width:14%; } table.dem .val { font-weight:600; width:19%; }
    h2 { font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:#02481f; border-bottom:1px solid #ddd; padding-bottom:3px; margin:14px 0 8px; }
    .field { margin-bottom:8px; }
    .field .l { font-weight:700; font-size:11px; } .field .v { white-space:pre-wrap; }
    .vitals span { display:inline-block; border:1px solid #ccc; border-radius:4px; padding:1px 8px; margin:0 6px 4px 0; }
    .sig { margin-top:36px; display:flex; justify-content:space-between; }
    .sig div { border-top:1px solid #999; width:30%; text-align:center; padding-top:4px; font-size:10px; color:#666; }
    @media print { body { padding:0 } }
</style></head>
<body>
    <div class="head">
        <div>
            <h1>{{ \App\Support\Settings::get('clinic_name', 'Amoleck Group Company LTD') }}</h1>
            <div class="sub">{{ \App\Support\Settings::get('clinic_address') }} · {{ \App\Support\Settings::get('clinic_phone') }}</div>
            <div class="sub" style="font-weight:700">PHYSIOTHERAPY ASSESSMENT — {{ $assessment->reference }}</div>
        </div>
        <div class="meta">
            Date: {{ $assessment->assessed_at->format('d M Y H:i') }}<br>
            Assessed by: {{ $assessment->assessor->name }}<br>
            @if ($assessment->visit)Visit: {{ $assessment->visit->visit_reference }}@endif
        </div>
    </div>

    <table class="dem">
        <tr><td class="lbl">Name</td><td class="val">{{ $assessment->patient->full_name }}</td><td class="lbl">No.</td><td class="val">{{ $assessment->patient->patient_number }}</td><td class="lbl">Age/Sex</td><td class="val">{{ $assessment->patient->date_of_birth?->age ?? '—' }} / {{ ucfirst($assessment->patient->sex ?? '—') }}</td></tr>
        <tr><td class="lbl">Address</td><td class="val">{{ $assessment->patient->address ?? '—' }}</td><td class="lbl">Phone</td><td class="val">{{ $assessment->patient->phone ?? '—' }}</td><td class="lbl">Occupation</td><td class="val">{{ $assessment->patient->occupation ?? '—' }}</td></tr>
        <tr><td class="lbl">Religion</td><td class="val">{{ $assessment->patient->religion ?? '—' }}</td><td class="lbl">Tribe</td><td class="val">{{ $assessment->patient->tribe ?? '—' }}</td><td class="lbl"></td><td class="val"></td></tr>
    </table>

    @php $n = 2; @endphp
    <h2>History</h2>
    @foreach (\App\Models\PtAssessment::SECTIONS['history'] as $f => $l)
        @if ($assessment->{$f})<div class="field"><span class="l">{{ $n }}. {{ $l }}:</span> <span class="v">{{ $assessment->{$f} }}</span></div>@endif
        @php $n++; @endphp
    @endforeach

    <h2>Examination</h2>
    @php $n = 9; @endphp
    @foreach (\App\Models\PtAssessment::SECTIONS['examination'] as $f => $l)
        @if ($assessment->{$f})<div class="field"><span class="l">{{ $n }}. {{ $l }}:</span> <span class="v">{{ $assessment->{$f} }}</span></div>@endif
        @if ($f === 'observation' && $assessment->vitals && array_filter($assessment->vitals))
            <div class="field vitals"><span class="l">10. Vitals:</span><br>
                @foreach (['bp'=>'BP','hr'=>'HR','rr'=>'RR','temp'=>'Temp','spo2'=>'SpO₂','weight'=>'Weight','height'=>'Height'] as $v => $lbl)
                    @if (!empty($assessment->vitals[$v]))<span>{{ $lbl }}: {{ $assessment->vitals[$v] }}</span>@endif
                @endforeach
            </div>
        @endif
        @php $n++; @endphp
    @endforeach

    <h2>Treatment Plan</h2>
    @php $n = 16; @endphp
    @foreach (\App\Models\PtAssessment::SECTIONS['plan'] as $f => $l)
        @if ($assessment->{$f})<div class="field"><span class="l">{{ $n }}. {{ $l }}:</span> <span class="v">{{ $assessment->{$f} }}</span></div>@endif
        @php $n++; @endphp
    @endforeach
    @if ($assessment->home_visit)<div class="field"><span class="l">20. Home Visit:</span> Recommended — {{ $assessment->home_visit_notes }}</div>@endif

    <div class="sig"><div>Assessing Therapist</div><div>Reviewed By</div><div>Date</div></div>
    <script>window.onload = () => window.print()</script>
</body></html>
