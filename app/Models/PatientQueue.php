<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientQueue extends Model
{
    protected $table = 'patient_queue';
    protected $guarded = [];

    protected $casts = ['checked_in_at' => 'datetime', 'called_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'is_walkin' => 'boolean'];

    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public static function nextNumber(): string
    {
        return 'Q-'.now()->format('md').'-'.str_pad(PatientQueue::whereDate('created_at', today())->count() + 1, 3, '0', STR_PAD_LEFT);
    }
}
