<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadFollowUp extends Model
{
    protected $fillable = [
        'lead_id', 'user_id', 'contacted_at', 'reached',
        'outcome', 'interest_level', 'notes', 'next_followup_at',
    ];

    protected function casts(): array
    {
        return [
            'contacted_at' => 'datetime',
            'reached' => 'boolean',
            'next_followup_at' => 'date',
        ];
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
