<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'sku', 'barcode', 'name', 'product_category_id', 'description', 'image_path',
        'purchase_price', 'selling_price', 'stock_quantity', 'min_stock_level',
        'unit', 'supplier_id', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2', 'selling_price' => 'decimal:2',
            'stock_quantity' => 'integer', 'min_stock_level' => 'integer',
        ];
    }

    public function category() { return $this->belongsTo(ProductCategory::class, 'product_category_id'); }

    public function supplier() { return $this->belongsTo(Supplier::class); }

    public function movements() { return $this->hasMany(StockMovement::class)->latest(); }

    public function isActive(): bool { return $this->status === self::STATUS_ACTIVE; }

    public function isLowStock(): bool { return $this->min_stock_level > 0 && $this->stock_quantity <= $this->min_stock_level && $this->stock_quantity > 0; }

    public function isOutOfStock(): bool { return $this->stock_quantity <= 0; }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::url($this->image_path) : null;
    }
}
