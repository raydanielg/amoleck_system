<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $fillable = ['name', 'description', 'is_active', 'sort_order'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function products() { return $this->hasMany(Product::class); }

    public function scopeActive($q) { return $q->where('is_active', true)->orderBy('sort_order')->orderBy('name'); }
}
