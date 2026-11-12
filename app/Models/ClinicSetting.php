<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicSetting extends Model
{
    protected $fillable = [
        'setting_key',
        'setting_value',
        'updated_by',
    ];
}
