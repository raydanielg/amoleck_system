<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'visit_reference',
        'patient_id',
        'visit_datetime',
        'chief_complaint',
        'assessment_notes',
        'treatment_notes',
        'plan_notes',
        'follow_up_date',
        'status',
        'completed_at',
        'void_reason',
        'voided_at',
        'voided_by',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_datetime' => 'datetime',
            'follow_up_date' => 'date',
            'completed_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function services()
    {
        return $this->hasMany(VisitService::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function voider()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
