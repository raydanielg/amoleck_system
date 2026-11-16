<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'ADMIN';

    public const ROLE_CLINIC_STAFF = 'CLINIC_STAFF';

    public const ROLE_RECEPTIONIST = 'RECEPTIONIST';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'must_change_password',
        'last_login_at',
        'avatar_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? asset('storage/'.$this->avatar_path) : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));

        return mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr($parts[1] ?? '', 0, 1));
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isActive(): bool
    {
        return strcasecmp((string) $this->status, self::STATUS_ACTIVE) === 0;
    }

    public function patients()
    {
        return $this->hasMany(Patient::class, 'created_by');
    }

    public function visits()
    {
        return $this->hasMany(Visit::class, 'recorded_by');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'recorded_by');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->whereNull('read_at')->count();
    }

    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /** Effective permission check: ADMIN all, then user overrides, then role defaults, then implied grants. */
    public function hasPermission(string $key): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        // An explicit override for this key is final (grant or deny).
        $override = $this->permissionOverrides->firstWhere('permission', $key);
        if ($override) {
            return (bool) $override->allowed;
        }

        if (in_array($key, Permissions::ROLE_DEFAULTS[$this->role] ?? [], true)) {
            return true;
        }

        // Another granted key may imply this one (e.g. accounting.reports ⇒ accounting.dashboard).
        foreach (Permissions::impliedBy($key) as $grantedKey) {
            if ($this->hasDirectPermission($grantedKey)) {
                return true;
            }
        }

        return false;
    }

    /** Direct grant only — override or role default, no implied resolution. */
    private function hasDirectPermission(string $key): bool
    {
        $override = $this->permissionOverrides->firstWhere('permission', $key);

        return $override
            ? (bool) $override->allowed
            : in_array($key, Permissions::ROLE_DEFAULTS[$this->role] ?? [], true);
    }

    /** Explains the access decision for a permission — used by the admin debugger. */
    public function permissionSource(string $key): array
    {
        if ($this->isAdmin()) {
            return ['allowed' => true, 'via' => 'admin role'];
        }

        $override = $this->permissionOverrides->firstWhere('permission', $key);
        if ($override) {
            return ['allowed' => (bool) $override->allowed, 'via' => $override->allowed ? 'user override (granted)' : 'user override (denied)'];
        }

        if (in_array($key, Permissions::ROLE_DEFAULTS[$this->role] ?? [], true)) {
            return ['allowed' => true, 'via' => 'role default'];
        }

        foreach (Permissions::impliedBy($key) as $grantedKey) {
            if ($this->hasDirectPermission($grantedKey)) {
                return ['allowed' => true, 'via' => 'implied by '.$grantedKey];
            }
        }

        return ['allowed' => false, 'via' => 'not granted'];
    }

    public function hasAnyPermission(array $keys): bool
    {
        foreach ($keys as $k) {
            if ($this->hasPermission($k)) {
                return true;
            }
        }

        return false;
    }
}
