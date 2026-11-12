<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PtAssessment;
use App\Models\Visit;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    private function canView(Request $r): void
    {
        abort_unless($r->user()->isAdmin() || $r->user()->hasPermission('clinical.view') || $r->user()->hasPermission('clinical.assess'), 403);
    }

    private function canAssess(Request $r): void
    {
        abort_unless($r->user()->isAdmin() || $r->user()->hasPermission('clinical.assess'), 403);
    }

    public function index(Request $r, Patient $patient)
    {
        $this->canView($r);
        $assessments = $patient->assessments()->with('assessor')->latest('assessed_at')->paginate(20);
        return view('assessments.index', compact('patient', 'assessments'));
    }

    public function create(Request $r, Patient $patient)
    {
        $this->canAssess($r);
        $visit = $r->filled('visit') ? Visit::where('patient_id', $patient->id)->findOrFail($r->visit) : null;
        return view('assessments.form', ['patient' => $patient, 'assessment' => new PtAssessment, 'visit' => $visit]);
    }

    private function rules(): array
    {
        $text = ['nullable', 'string', 'max:20000'];
        $rules = ['chief_complaint' => $text, 'medical_diagnosis' => $text, 'hpi' => $text,
            'past_medical_history' => $text, 'family_social_history' => $text, 'current_medication' => $text,
            'investigations' => $text, 'observation' => $text, 'chest_assessment' => $text,
            'local_assessment' => $text, 'neurological_assessment' => $text, 'functional_assessment' => $text,
            'summary_findings' => $text, 'pt_impression' => $text, 'treatment_aim' => $text,
            'short_term_plan' => $text, 'long_term_plan' => $text, 'evaluation_followup' => $text,
            'home_visit_notes' => $text, 'prescribed_equipment' => $text,
            'home_visit' => ['nullable', 'boolean'], 'visit_id' => ['nullable', 'exists:visits,id']];
        foreach (['bp', 'hr', 'rr', 'temp', 'spo2', 'weight', 'height'] as $v) {
            $rules["vitals.$v"] = ['nullable', 'string', 'max:30'];
        }
        return $rules;
    }

    public function store(Request $r, Patient $patient)
    {
        $this->canAssess($r);
        $data = $r->validate($this->rules());
        abort_if(! empty($data['visit_id']) && ! Visit::where('patient_id', $patient->id)->where('id', $data['visit_id'])->exists(), 422);

        $a = PtAssessment::create($data + [
            'reference' => 'PTA-'.strtoupper(\Illuminate\Support\Str::random(8)),
            'patient_id' => $patient->id,
            'assessed_by' => $r->user()->id,
            'branch_id' => $patient->branch_id ?? $r->user()->employee?->branch_id,
            'home_visit' => $r->boolean('home_visit'),
            'assessed_at' => now(),
        ]);
        AuditLogger::log('assessment.create', 'PtAssessment', $a->id, null, ['patient' => $patient->id]);
        return redirect()->route('assessments.show', $a)->with('success', 'Assessment saved');
    }

    public function show(Request $r, PtAssessment $assessment)
    {
        $this->canView($r);
        $assessment->load('patient', 'visit', 'assessor');
        return view('assessments.show', compact('assessment'));
    }

    public function edit(Request $r, PtAssessment $assessment)
    {
        $this->canAssess($r);
        return view('assessments.form', ['patient' => $assessment->patient, 'assessment' => $assessment, 'visit' => $assessment->visit]);
    }

    public function update(Request $r, PtAssessment $assessment)
    {
        $this->canAssess($r);
        $data = $r->validate($this->rules());
        unset($data['visit_id']);
        AuditLogger::log('assessment.update', 'PtAssessment', $assessment->id, $assessment->only('chief_complaint', 'pt_impression'), $data);
        $assessment->update($data + ['home_visit' => $r->boolean('home_visit')]);
        return redirect()->route('assessments.show', $assessment)->with('success', 'Assessment updated');
    }

    public function print(Request $r, PtAssessment $assessment)
    {
        $this->canView($r);
        $assessment->load('patient', 'visit', 'assessor');
        return view('assessments.print', compact('assessment'));
    }
}
