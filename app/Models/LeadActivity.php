<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadActivity extends Model
{
    protected $fillable = ['lead_id', 'user_id', 'type', 'description', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
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
