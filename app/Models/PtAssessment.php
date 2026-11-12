<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PtAssessment extends Model
{
    protected $guarded = [];
    protected $casts = ['vitals' => 'array', 'assessed_at' => 'datetime', 'home_visit' => 'boolean'];

    public const SECTIONS = [
        'history' => [
            'chief_complaint' => 'Chief Complaint',
            'medical_diagnosis' => 'Medical Diagnosis',
            'hpi' => 'History of Presenting Illness',
            'past_medical_history' => 'Past Medical History',
            'family_social_history' => 'Relevant Family / Social History',
            'current_medication' => 'Current Medication',
            'investigations' => 'Investigations',
        ],
        'examination' => [
            'observation' => 'Observation',
            'chest_assessment' => 'Chest Assessment',
            'local_assessment' => 'Local Assessment',
            'neurological_assessment' => 'Neurological Assessment',
            'functional_assessment' => 'Functional Assessment',
            'summary_findings' => 'Summary of Findings',
            'pt_impression' => 'Physiotherapy Impression',
        ],
        'plan' => [
            'treatment_aim' => 'Aim of Treatment',
            'short_term_plan' => 'Short-Term Plan of Treatment',
            'long_term_plan' => 'Long-Term Plan of Treatment',
            'evaluation_followup' => 'Evaluation & Follow-Up',
            'prescribed_equipment' => 'Prescribed Equipment',
        ],
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function visit() { return $this->belongsTo(Visit::class); }
    public function assessor() { return $this->belongsTo(User::class, 'assessed_by'); }
}
