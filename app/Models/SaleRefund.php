<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleRefund extends Model
{
    protected $fillable = ['refund_reference', 'sale_id', 'amount', 'reason', 'refunded_by'];

    protected function casts(): array { return ['amount' => 'decimal:2']; }

    public function sale() { return $this->belongsTo(Sale::class); }

    public function refunder() { return $this->belongsTo(User::class, 'refunded_by'); }
}
