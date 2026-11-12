<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    public const STATUS_PENDING = 'pending_payment';
    public const STATUS_PARTIAL = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_PART_REFUNDED = 'partially_refunded';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'sale_reference', 'customer_id', 'customer_name', 'status', 'subtotal',
        'discount_amount', 'discount_reason', 'tax_percent', 'tax_amount', 'total',
        'amount_paid', 'balance', 'notes', 'sold_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2', 'discount_amount' => 'decimal:2',
            'tax_percent' => 'decimal:2', 'tax_amount' => 'decimal:2',
            'total' => 'decimal:2', 'amount_paid' => 'decimal:2', 'balance' => 'decimal:2',
        ];
    }

    public function customer() { return $this->belongsTo(Customer::class); }

    public function seller() { return $this->belongsTo(User::class, 'sold_by'); }

    public function items() { return $this->hasMany(SaleItem::class); }

    public function payments() { return $this->hasMany(SalePayment::class); }

    public function refunds() { return $this->hasMany(SaleRefund::class); }

    public function returns() { return $this->hasMany(SaleReturn::class); }

    /** Recompute paid/balance/status from payments minus refunds. */
    public function refreshPaymentState(): void
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return;
        }
        $paid = $this->payments()->where('status', SalePayment::STATUS_COMPLETED)->sum('amount');
        $refunded = $this->refunds()->sum('amount');
        $net = max(0, bcsub((string) $paid, (string) $refunded, 2));

        $this->amount_paid = $net;
        $this->balance = max(0, bcsub((string) $this->total, $net, 2));

        if (bccomp($refunded, '0', 2) > 0) {
            $this->status = bccomp($refunded, (string) $paid, 2) >= 0 ? self::STATUS_REFUNDED : self::STATUS_PART_REFUNDED;
        } else {
            $this->status = $this->balance <= 0 ? self::STATUS_PAID : ($net > 0 ? self::STATUS_PARTIAL : self::STATUS_PENDING);
        }
        $this->save();
    }

    /** Cost of goods sold for this sale. */
    public function cogs(): string
    {
        return (string) $this->items->sum(fn ($i) => bcmul((string) $i->unit_cost, (string) $i->quantity, 2));
    }
}
