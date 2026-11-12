<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagementKpi extends Model
{
    protected $guarded = [];
    protected $casts = ['target' => 'decimal:2', 'is_active' => 'boolean'];
}
