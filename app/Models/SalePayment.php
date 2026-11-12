<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_VOIDED = 'voided';

    protected $fillable = ['sale_id', 'amount', 'payment_method', 'payment_date', 'status', 'received_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payment_date' => 'date'];
    }

    public function sale() { return $this->belongsTo(Sale::class); }

    public function receiver() { return $this->belongsTo(User::class, 'received_by'); }
}
