<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagementInsight extends Model
{
    protected $guarded = [];
    protected $casts = ['data' => 'array', 'acknowledged_at' => 'datetime'];
}
