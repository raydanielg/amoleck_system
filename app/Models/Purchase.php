<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = ['purchase_reference', 'supplier_id', 'status', 'total', 'amount_paid', 'notes', 'received_at', 'created_by'];

    protected function casts(): array
    {
        return ['total' => 'decimal:2', 'amount_paid' => 'decimal:2', 'received_at' => 'datetime'];
    }

    public function supplier() { return $this->belongsTo(Supplier::class); }

    public function items() { return $this->hasMany(PurchaseItem::class); }

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
