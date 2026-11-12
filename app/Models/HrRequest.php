<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrRequest extends Model
{
    protected $guarded = [];
    protected $casts = ['decided_at' => 'datetime'];

    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function decider(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
