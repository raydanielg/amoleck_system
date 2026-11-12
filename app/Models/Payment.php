<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_VOIDED = 'voided';

    public const VERIFICATION_VERIFIED = 'verified';
    public const VERIFICATION_PENDING = 'pending';
    public const VERIFICATION_REJECTED = 'rejected';

    protected $fillable = [
        'payment_reference',
        'invoice_id',
        'patient_id',
        'amount',
        'payment_method',
        'payment_date',
        'status',
        'received_by',
        'notes',
            'transaction_reference',
        'verification_status',
        'verified_by',
        'verified_at',
        'proof_note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function refundedAmount(): string
    {
        return (string) $this->refunds()->where('status', Refund::STATUS_REFUNDED)->sum('amount');
    }

    public function refundableAmount(): string
    {
        return bcsub((string) $this->amount, $this->refundedAmount(), 2);
    }
}
