<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\AuditLogger;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $query = Patient::query()->withCount('visits')->latest();

        if ($search = trim((string) $request->query('q'))) {
            $like = '%'.str_replace(' ', '%', $search).'%';
            $query->where(function ($q) use ($like) {
                $q->where('full_name', 'like', $like)
                    ->orWhere('patient_number', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('patients.index', [
            'patients' => $query->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('patients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePatient($request);

        $duplicates = $this->findDuplicates($validated);

        if ($duplicates->isNotEmpty() && ! $request->boolean('confirm_duplicate') && Settings::bool('patient_duplicate_warning_enabled', true)) {
            return back()
                ->withInput()
                ->with('warning', 'Possible duplicate patient found: '.$duplicates->pluck('full_name')->implode(', ').'. Tick "Register anyway" to continue.')
                ->with('duplicate_ids', $duplicates->pluck('id')->all());
        }

        $patient = DB::transaction(function () use ($validated, $request) {
            $number = Settings::nextNumber('patient_number_prefix', 'patient_number_next', 'PT');

            return Patient::create([
                ...$validated,
                'patient_number' => $number,
                'status' => Patient::STATUS_ACTIVE,
                'created_by' => $request->user()->id,
            ]);
        });

        AuditLogger::log('patient.created', 'patient', $patient->id, null, $patient->only([
            'patient_number', 'full_name', 'sex', 'date_of_birth', 'phone',
        ]));

        try {
            \App\Services\Messaging\MessagingService::notifyEvent('patient.registered', $patient->phone, [
                'patient_name' => $patient->full_name, 'patient_number' => $patient->patient_number,
                'clinic_name' => \App\Support\Settings::get('clinic_name'), 'clinic_phone' => \App\Support\Settings::get('clinic_phone'),
            ], ['patient_id' => $patient->id, 'dedupe' => $patient->id]);
        } catch (\Throwable) {}

        \App\Services\NotificationService::notifyAdmins(
            'patient.registered', 'New patient registered',
            "{$patient->full_name} ({$patient->patient_number}) registered by {$request->user()->name}",
            route('patients.show', $patient),
        );

        return redirect()->route('patients.show', $patient)
            ->with('success', "Patient {$patient->patient_number} registered successfully.");
    }

    public function show(Patient $patient): View
    {
        $patient->load(['visits.services', 'visits.recorder', 'creator']);

        return view('patients.show', ['patient' => $patient]);
    }

    public function edit(Patient $patient): View
    {
        return view('patients.edit', ['patient' => $patient]);
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $validated = $this->validatePatient($request, $patient);
        $old = $patient->only(array_keys($validated));

        $patient->update($validated);

        AuditLogger::log('patient.updated', 'patient', $patient->id, $old, $patient->only(array_keys($validated)));

        return redirect()->route('patients.show', $patient)->with('success', 'Patient updated.');
    }

    public function archive(Request $request, Patient $patient): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $validated = $request->validate(['archive_reason' => ['required', 'string', 'max:500']]);

        $patient->update([
            'status' => Patient::STATUS_ARCHIVED,
            'archive_reason' => $validated['archive_reason'],
            'archived_at' => now(),
            'archived_by' => $request->user()->id,
        ]);

        AuditLogger::log('patient.archived', 'patient', $patient->id, null, ['reason' => $validated['archive_reason']]);

        return redirect()->route('patients.show', $patient)->with('success', 'Patient archived.');
    }

    public function restore(Patient $patient): RedirectResponse
    {
        $patient->update([
            'status' => Patient::STATUS_ACTIVE,
            'archive_reason' => null,
            'archived_at' => null,
            'archived_by' => null,
        ]);

        AuditLogger::log('patient.restored', 'patient', $patient->id);

        return redirect()->route('patients.show', $patient)->with('success', 'Patient restored.');
    }

    public function print(Patient $patient): View
    {
        $patient->load(['visits.services', 'visits.recorder']);

        return view('patients.print', ['patient' => $patient]);
    }

    private function validatePatient(Request $request, ?Patient $patient = null): array
    {
        $phoneRule = Settings::bool('patient_require_phone') ? 'required' : 'nullable';
        $dobRule = Settings::bool('patient_require_dob', true) ? 'required' : 'nullable';
        $sexRule = Settings::bool('patient_require_sex', true) ? 'required' : 'nullable';
        $emergency = Settings::bool('patient_emergency_contact_enabled', true);

        return $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'sex' => [$sexRule, 'string', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
            'date_of_birth' => [$dobRule, 'date', 'before_or_equal:today'],
            'phone' => [$phoneRule, 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'emergency_contact_name' => [$emergency ? 'nullable' : 'exclude', 'string', 'max:255'],
            'emergency_contact_relationship' => [$emergency ? 'nullable' : 'exclude', 'string', 'max:100'],
            'emergency_contact_phone' => [$emergency ? 'nullable' : 'exclude', 'string', 'max:30'],
            'medical_history' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function findDuplicates(array $data)
    {
        return Patient::where('status', Patient::STATUS_ACTIVE)
            ->where(function ($q) use ($data) {
                if (! empty($data['phone'])) {
                    $q->orWhere('phone', $data['phone']);
                }
                if (! empty($data['date_of_birth'])) {
                    $q->orWhere(function ($q2) use ($data) {
                        $q2->where('full_name', 'like', $data['full_name'])
                            ->whereDate('date_of_birth', $data['date_of_birth']);
                    });
                }
            })
            ->limit(5)
            ->get(['id', 'full_name', 'patient_number']);
    }
}
