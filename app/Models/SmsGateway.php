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

    /**
     * Secrets are encrypted at rest. If APP_KEY has rotated since the value
     * was stored, return null instead of throwing a DecryptException (500).
     * webhook_secret may hold a legacy plaintext value — return it as-is.
     */
    private function secretGet(string $key): ?string
    {
        $v = $this->attributes[$key] ?? null;
        if (! $v) {
            return null;
        }
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($v);
        } catch (\Throwable) {
            return $key === 'webhook_secret' ? $v : null;
        }
    }

    private function secretSet(string $key, ?string $v): void
    {
        $this->attributes[$key] = $v ? \Illuminate\Support\Facades\Crypt::encryptString($v) : null;
    }

    public function getApiKeyAttribute($v): ?string { return $this->secretGet('api_key'); }
    public function setApiKeyAttribute($v): void { $this->secretSet('api_key', $v); }
    public function getApiSecretAttribute($v): ?string { return $this->secretGet('api_secret'); }
    public function setApiSecretAttribute($v): void { $this->secretSet('api_secret', $v); }
    public function getPasswordAttribute($v): ?string { return $this->secretGet('password'); }
    public function setPasswordAttribute($v): void { $this->secretSet('password', $v); }
    public function getWebhookSecretAttribute($v): ?string { return $this->secretGet('webhook_secret'); }
    public function setWebhookSecretAttribute($v): void { $this->secretSet('webhook_secret', $v); }
}
