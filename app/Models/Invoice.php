<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    public const STATUS_ISSUED = 'issued';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'invoice_reference',
        'patient_id',
        'visit_id',
        'issued_by',
        'issue_date',
        'status',
        'subtotal',
        'discount_amount',
        'discount_reason',
        'total',
        'amount_paid',
        'balance',
        'notes',
        'void_reason',
        'type',
        'due_date',
        'terms',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function serviceFulfilments(): HasMany
    {
        return $this->hasMany(ServiceFulfilment::class);
    }

    public function productFulfilments(): HasMany
    {
        return $this->hasMany(ProductFulfilment::class);
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    /** Recompute amount_paid / balance / status from completed payments minus refunds. */
    public function refreshPaymentState(): void
    {
        if ($this->isVoided()) {
            return;
        }

        $paid = $this->payments()->where('status', Payment::STATUS_COMPLETED)
            ->where('verification_status', Payment::VERIFICATION_VERIFIED)->sum('amount');
        $refunded = Refund::whereIn('payment_id', $this->payments()->pluck('id'))
            ->where('status', Refund::STATUS_REFUNDED)
            ->sum('amount');
        $net = max(0, $paid - $refunded);

        $this->amount_paid = $net;
        $this->balance = max(0, bcsub((string) $this->total, (string) $net, 2));
        $this->status = $this->balance <= 0
            ? self::STATUS_PAID
            : ($net > 0 ? self::STATUS_PARTIALLY_PAID : self::STATUS_ISSUED);
        $this->save();
    }
}
