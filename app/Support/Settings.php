<?php

namespace App\Support;

use App\Models\ClinicSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Settings
{
    protected static array $loaded = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, static::$loaded)) {
            static::$loaded[$key] = ClinicSetting::where('setting_key', $key)->value('setting_value');
        }

        return static::$loaded[$key] ?? $default;
    }

    public static function set(string $key, mixed $value, ?int $userId = null): void
    {
        ClinicSetting::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value, 'updated_by' => $userId]
        );

        static::$loaded[$key] = $value;
        Cache::forget('clinic_settings.'.$key);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = static::get($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = static::get($key);

        return $value === null ? $default : (int) $value;
    }

    /**
     * Atomically allocate the next number for a sequence stored in settings.
     * Must be called inside a database transaction.
     */
    public static function nextNumber(string $prefixKey, string $counterKey, string $defaultPrefix): string
    {
        $row = DB::table('clinic_settings')->where('setting_key', $counterKey)->lockForUpdate()->first();
        $next = ($row?->setting_value ? (int) $row->setting_value : 0) + 1;
        static::set($counterKey, (string) $next);

        $prefix = static::get($prefixKey, $defaultPrefix) ?: $defaultPrefix;

        return $prefix.'-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public static function paymentMethods(): array
    {
        $raw = static::get('payment_methods', 'Cash,Bank Transfer,Mobile Money,Card,Other');

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
