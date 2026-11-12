<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitService extends Model
{
    protected $fillable = [
        'visit_id',
        'service_id',
        'service_name_snapshot',
        'service_code_snapshot',
        'price_snapshot',
        'notes',
    ];

    protected function casts(): array
    {
        return ['price_snapshot' => 'decimal:2'];
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
