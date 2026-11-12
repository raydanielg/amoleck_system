<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'patient_number',
        'full_name',
        'sex',
        'date_of_birth',
        'phone',
        'address',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'medical_history',
        'status',
        'archive_reason',
        'archived_at',
        'archived_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    public function visits()
    {
        return $this->hasMany(Visit::class)->orderBy('visit_datetime', 'desc');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class)->latest('issue_date');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class)->latest('payment_date');
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class)->orderBy('due_date');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class)->orderBy('scheduled_at', 'desc');
    }

    public function outstandingBalance(): string
    {
        return (string) $this->invoices()
            ->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIALLY_PAID])
            ->sum('balance');
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }
}
