<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public const TYPE_RECEIVED = 'received';
    public const TYPE_ADJUSTED = 'adjusted';
    public const TYPE_SOLD = 'sold';
    public const TYPE_RETURNED = 'returned';
    public const TYPE_PURCHASE_RECEIVED = 'purchase_received';

    protected $fillable = ['product_id', 'type', 'quantity', 'stock_after', 'reference', 'reason', 'user_id'];

    public function product() { return $this->belongsTo(Product::class); }

    public function user() { return $this->belongsTo(User::class); }
}
