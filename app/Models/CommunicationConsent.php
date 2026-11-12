<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunicationConsent extends Model
{
    protected $fillable = ['patient_id', 'customer_id', 'phone', 'sms_opt_out'];

    protected function casts(): array { return ['sms_opt_out' => 'boolean']; }

    public static function optedOut(string $phone): bool
    {
        return static::where('phone', $phone)->where('sms_opt_out', true)->exists();
    }
}
