<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Settings::bool('appointments_enabled', true), 404);

        $query = Appointment::with('patient', 'service', 'staff')->latest('scheduled_at');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('appointment_reference', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($p) => $p->where('full_name', 'like', "%{$search}%"));
            });
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        match ($request->query('when')) {
            'upcoming' => $query->where('scheduled_at', '>=', now())->whereIn('status', [Appointment::STATUS_SCHEDULED, Appointment::STATUS_CONFIRMED]),
            'today' => $query->whereDate('scheduled_at', today()),
            'past' => $query->where('scheduled_at', '<', now()),
            default => null,
        };

        return view('appointments.index', [
            'appointments' => $query->paginate(15)->withQueryString(),
            'patients' => Patient::where('status', Patient::STATUS_ACTIVE)->orderBy('full_name')->get(['id', 'full_name', 'patient_number']),
            'services' => Service::active()->get(['id', 'name']),
            'staff' => User::where('status', User::STATUS_ACTIVE)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Settings::bool('appointments_enabled', true), 404);

        $validated = $this->validateAppointment($request);

        $appointment = Appointment::create([
            ...$validated,
            'appointment_reference' => \App\Support\Settings::nextNumber('appointment_reference_prefix', 'appointment_reference_next', 'APT'),
            'status' => Appointment::STATUS_SCHEDULED,
            'created_by' => $request->user()->id,
        ]);

        AuditLogger::log('appointment.created', 'appointment', $appointment->id, null, $appointment->only(['appointment_reference', 'patient_id', 'scheduled_at']));

        return back()->with('success', "Appointment {$appointment->appointment_reference} scheduled.");
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $validated = $this->validateAppointment($request);

        $old = $appointment->only(['patient_id', 'service_id', 'staff_id', 'scheduled_at', 'notes']);
        $appointment->update($validated);

        AuditLogger::log('appointment.updated', 'appointment', $appointment->id, $old, $appointment->fresh()->only(array_keys($old)));

        return back()->with('success', 'Appointment updated.');
    }

    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $validated = $request->validate(['status' => ['required', Rule::in(Appointment::STATUSES)]]);

        $old = $appointment->status;
        $appointment->update(['status' => $validated['status']]);

        AuditLogger::log('appointment.status_changed', 'appointment', $appointment->id, ['status' => $old], ['status' => $validated['status']]);

        return back()->with('success', "Appointment marked {$validated['status']}.");
    }

    private function validateAppointment(Request $request): array
    {
        return $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'staff_id' => ['nullable', 'integer', 'exists:users,id'],
            'scheduled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
