<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Patient Record — {{ $patient->patient_number }}</title>
    <style>
        body { font-family: sans-serif; color: #111; font-size: 12px; margin: 30px; }
        h1 { font-size: 18px; margin-bottom: 2px; }
        h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        td, th { border: 1px solid #ccc; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #f4f4f4; font-size: 11px; }
        .header { border-bottom: 2px solid #16834A; padding-bottom: 10px; margin-bottom: 15px; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>{{ \App\Support\Settings::get('clinic_name', config('app.name')) }}</h1>
        <div class="muted">{{ \App\Support\Settings::get('clinic_address') }} {{ \App\Support\Settings::get('clinic_phone') }} {{ \App\Support\Settings::get('clinic_email') }}</div>
    </div>

    <h2>Patient Record</h2>
    <table>
        <tr><th>Patient No</th><td>{{ $patient->patient_number }}</td><th>Status</th><td>{{ ucfirst($patient->status) }}</td></tr>
        <tr><th>Full Name</th><td>{{ $patient->full_name }}</td><th>Sex</th><td>{{ ucfirst($patient->sex ?? '—') }}</td></tr>
        <tr><th>Date of Birth</th><td>{{ $patient->date_of_birth?->format('d/m/Y') ?? '—' }}</td><th>Phone</th><td>{{ $patient->phone ?? '—' }}</td></tr>
        <tr><th>Address</th><td colspan="3">{{ $patient->address ?? '—' }}</td></tr>
        <tr><th>Medical History</th><td colspan="3">{{ $patient->medical_history ?? '—' }}</td></tr>
    </table>

    <h2>Visit History</h2>
    <table>
        <tr><th>Reference</th><th>Date</th><th>Services</th><th>Status</th></tr>
        @forelse ($patient->visits as $visit)
            <tr>
                <td>{{ $visit->visit_reference }}</td>
                <td>{{ $visit->visit_datetime->format('d/m/Y H:i') }}</td>
                <td>{{ $visit->services->pluck('service_name_snapshot')->implode(', ') }}</td>
                <td>{{ ucfirst($visit->status) }}</td>
            </tr>
        @empty
            <tr><td colspan="4">No visits.</td></tr>
        @endforelse
    </table>

    <p class="muted" style="margin-top:20px;">Printed {{ now()->format('d/m/Y H:i') }} · {{ \App\Support\Settings::get('report_footer') }}</p>
</body>
</html>
