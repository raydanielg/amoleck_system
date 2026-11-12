<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Service;
use App\Models\Visit;
use App\Services\AuditLogger;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VisitController extends Controller
{
    public function index(Request $request): View
    {
        $query = Visit::with('patient', 'recorder', 'services')->latest('visit_datetime');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('visit_reference', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($p) => $p->where('full_name', 'like', "%{$search}%")
                        ->orWhere('patient_number', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->query('from')) {
            $query->where('visit_datetime', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->where('visit_datetime', '<=', $to.' 23:59:59');
        }

        return view('visits.index', ['visits' => $query->paginate(15)->withQueryString()]);
    }

    public function create(Patient $patient): View
    {
        abort_if($patient->isArchived(), 403, 'Cannot create a visit for an archived patient.');

        return view('visits.create', [
            'patient' => $patient,
            'services' => Service::active()->with('category')->get(),
            'multipleServices' => Settings::bool('visit_multiple_services', true),
        ]);
    }

    public function store(Request $request, Patient $patient): RedirectResponse
    {
        abort_if($patient->isArchived(), 403);

        $validated = $this->validateVisit($request);

        $visit = DB::transaction(function () use ($validated, $request, $patient) {
            $visit = Visit::create([
                'visit_reference' => Settings::nextNumber('visit_reference_prefix', 'visit_reference_next', 'VS'),
                'patient_id' => $patient->id,
                'visit_datetime' => $validated['visit_datetime'],
                'chief_complaint' => $validated['chief_complaint'] ?? null,
                'assessment_notes' => $validated['assessment_notes'] ?? null,
                'treatment_notes' => $validated['treatment_notes'] ?? null,
                'plan_notes' => $validated['plan_notes'] ?? null,
                'follow_up_date' => $validated['follow_up_date'] ?? null,
                'status' => Visit::STATUS_DRAFT,
                'recorded_by' => $request->user()->id,
            ]);

            $this->syncServices($visit, $validated['services'] ?? []);

            if (! empty($validated['follow_up_date'])) {
                \App\Models\FollowUp::create([
                    'patient_id' => $patient->id,
                    'visit_id' => $visit->id,
                    'due_date' => $validated['follow_up_date'],
                    'status' => \App\Models\FollowUp::STATUS_PENDING,
                    'notes' => 'Follow-up from visit '.$visit->visit_reference,
                    'recorded_by' => $request->user()->id,
                ]);
            }

            return $visit;
        });

        AuditLogger::log('visit.created', 'visit', $visit->id, null, ['reference' => $visit->visit_reference, 'patient_id' => $patient->id]);

        if ($request->boolean('complete')) {
            return $this->complete($request->merge(['visit' => $visit->id]), $visit);
        }

        return redirect()->route('visits.show', $visit)->with('success', "Visit {$visit->visit_reference} saved as draft.");
    }

    public function show(Visit $visit): View
    {
        $visit->load(['patient', 'services.service', 'recorder', 'voider']);

        return view('visits.show', [
            'visit' => $visit,
            'canEdit' => $this->canEdit($visit, request()->user()),
        ]);
    }

    public function edit(Visit $visit): View
    {
        abort_unless($this->canEdit($visit, request()->user()), 403, 'This visit can no longer be edited.');

        return view('visits.edit', [
            'visit' => $visit->load('services'),
            'patient' => $visit->patient,
            'services' => Service::active()->with('category')->get(),
            'multipleServices' => Settings::bool('visit_multiple_services', true),
        ]);
    }

    public function update(Request $request, Visit $visit): RedirectResponse
    {
        abort_unless($this->canEdit($visit, $request->user()), 403);

        $validated = $this->validateVisit($request, $visit);

        $old = $visit->only([
            'visit_datetime', 'chief_complaint', 'assessment_notes',
            'treatment_notes', 'plan_notes', 'follow_up_date',
        ]) + ['services' => $visit->services->pluck('service_name_snapshot')->all()];

        DB::transaction(function () use ($visit, $validated) {
            $visit->update([
                'visit_datetime' => $validated['visit_datetime'],
                'chief_complaint' => $validated['chief_complaint'] ?? null,
                'assessment_notes' => $validated['assessment_notes'] ?? null,
                'treatment_notes' => $validated['treatment_notes'] ?? null,
                'plan_notes' => $validated['plan_notes'] ?? null,
                'follow_up_date' => $validated['follow_up_date'] ?? null,
            ]);

            $visit->services()->delete();
            $this->syncServices($visit, $validated['services'] ?? []);
        });

        AuditLogger::log('visit.updated', 'visit', $visit->id, $old, $visit->fresh()->only(array_keys($old)));

        return redirect()->route('visits.show', $visit)->with('success', 'Visit updated.');
    }

    public function complete(Request $request, Visit $visit): RedirectResponse
    {
        abort_if($visit->isVoided() || $visit->isCompleted(), 422, 'Visit cannot be completed.');

        $visit->update(['status' => Visit::STATUS_COMPLETED, 'completed_at' => now()]);

        AuditLogger::log('visit.completed', 'visit', $visit->id);

        return redirect()->route('visits.show', $visit)->with('success', "Visit {$visit->visit_reference} completed.");
    }

    public function void(Request $request, Visit $visit): RedirectResponse
    {
        $validated = $request->validate(['void_reason' => ['required', 'string', 'max:500']]);

        abort_if($visit->isVoided(), 422);

        $visit->update([
            'status' => Visit::STATUS_VOIDED,
            'void_reason' => $validated['void_reason'],
            'voided_at' => now(),
            'voided_by' => $request->user()->id,
        ]);

        AuditLogger::log('visit.voided', 'visit', $visit->id, null, ['reason' => $validated['void_reason']]);

        return redirect()->route('visits.show', $visit)->with('success', 'Visit voided.');
    }

    public function print(Visit $visit): View
    {
        $visit->load(['patient', 'services.service', 'recorder']);

        return view('visits.print', ['visit' => $visit]);
    }

    private function canEdit(Visit $visit, $user): bool
    {
        if ($visit->isVoided()) {
            return false;
        }

        if ($visit->isDraft()) {
            return true;
        }

        // completed visit
        if ($user->isAdmin()) {
            return true;
        }

        $window = Settings::int('visit_correction_window_hours', 0);

        return $window > 0 && $visit->completed_at && $visit->completed_at->diffInHours(now()) <= $window;
    }

    private function validateVisit(Request $request, ?Visit $visit = null): array
    {
        $requireService = Settings::bool('visit_require_service', true);
        $requireNotes = Settings::bool('visit_require_notes');
        $multiple = Settings::bool('visit_multiple_services', true);

        $serviceRule = $requireService ? 'required' : 'nullable';
        if (! $multiple) {
            $serviceRule .= '|size:1';
        }

        return $request->validate([
            'visit_datetime' => ['required', 'date'],
            'chief_complaint' => ['nullable', 'string', 'max:2000'],
            'assessment_notes' => ['nullable', 'string', 'max:5000'],
            'treatment_notes' => [$requireNotes ? 'required' : 'nullable', 'string', 'max:5000'],
            'plan_notes' => ['nullable', 'string', 'max:5000'],
            'follow_up_date' => ['nullable', 'date'],
            'services' => [$serviceRule, 'array'],
            'services.*' => ['integer', 'exists:services,id'],
        ]);
    }

    private function syncServices(Visit $visit, array $serviceIds): void
    {
        foreach ($serviceIds as $serviceId) {
            $service = Service::find($serviceId);
            if (! $service || ! $service->is_active) {
                continue;
            }

            $visit->services()->create([
                'service_id' => $service->id,
                'service_name_snapshot' => $service->name,
                'service_code_snapshot' => $service->service_code,
                'price_snapshot' => $service->standard_price,
            ]);
        }
    }
}
