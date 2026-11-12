<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'service_code',
        'service_category_id',
        'name',
        'description',
        'standard_price',
        'duration_minutes',
        'requires_notes',
        'is_active',
        'sort_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'standard_price' => 'decimal:2',
            'requires_notes' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function priceHistories()
    {
        return $this->hasMany(ServicePriceHistory::class)->latest();
    }
}
