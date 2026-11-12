<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Visit {{ $visit->visit_reference }}</title>
    <style>
        body { font-family: sans-serif; color: #111; font-size: 12px; margin: 30px; }
        h1 { font-size: 18px; margin-bottom: 2px; }
        h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        td, th { border: 1px solid #ccc; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #f4f4f4; font-size: 11px; width: 25%; }
        .header { border-bottom: 2px solid #16834A; padding-bottom: 10px; margin-bottom: 15px; }
        .sig { margin-top: 50px; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>{{ \App\Support\Settings::get('clinic_name', config('app.name')) }}</h1>
        <div class="muted">{{ \App\Support\Settings::get('clinic_address') }} {{ \App\Support\Settings::get('clinic_phone') }} {{ \App\Support\Settings::get('clinic_email') }}</div>
    </div>

    <h2>Visit Record — {{ $visit->visit_reference }} ({{ ucfirst($visit->status) }})</h2>
    <table>
        <tr><th>Patient</th><td>{{ $visit->patient?->full_name }} ({{ $visit->patient?->patient_number }})</td></tr>
        <tr><th>Date & Time</th><td>{{ $visit->visit_datetime->format('d/m/Y H:i') }}</td></tr>
        <tr><th>Services</th><td>{{ $visit->services->pluck('service_name_snapshot')->implode(', ') ?: '—' }}</td></tr>
        <tr><th>Chief Complaint</th><td>{{ $visit->chief_complaint ?? '—' }}</td></tr>
        <tr><th>Assessment</th><td>{{ $visit->assessment_notes ?? '—' }}</td></tr>
        <tr><th>Treatment Notes</th><td>{{ $visit->treatment_notes ?? '—' }}</td></tr>
        <tr><th>Plan / Advice</th><td>{{ $visit->plan_notes ?? '—' }}</td></tr>
        <tr><th>Follow-up Date</th><td>{{ $visit->follow_up_date?->format('d/m/Y') ?? '—' }}</td></tr>
        <tr><th>Recorded By</th><td>{{ $visit->recorder?->name ?? '—' }}</td></tr>
    </table>

    <div class="sig">
        <div>{{ \App\Support\Settings::get('report_signature') }}</div>
        <div class="muted">Signature: ______________________</div>
    </div>
    <p class="muted" style="margin-top:20px;">Printed {{ now()->format('d/m/Y H:i') }} · {{ \App\Support\Settings::get('report_footer') }}</p>
</body>
</html>
