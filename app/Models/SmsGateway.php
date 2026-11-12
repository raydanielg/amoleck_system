<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsGateway extends Model
{
    protected $fillable = [
        'provider', 'label', 'base_url', 'endpoint', 'api_key', 'api_secret',
        'username', 'password', 'sender_id', 'country_code', 'timeout_seconds',
        'webhook_secret', 'enabled', 'cost_per_segment', 'manual_balance', 'balance_checked_at',
    ];

    protected $hidden = ['api_key', 'api_secret', 'password', 'webhook_secret'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'api_key' => 'encrypted',
            'api_secret' => 'encrypted',
            'password' => 'encrypted',
            'cost_per_segment' => 'decimal:4',
            'balance_checked_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        return static::first() ?? static::create(['provider' => 'generic']);
    }

    public function isConfigured(): bool
    {
        return filled($this->base_url) && filled($this->endpoint);
    }
}
