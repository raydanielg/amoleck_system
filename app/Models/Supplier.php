<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['name', 'contact_person', 'phone', 'email', 'address', 'company', 'notes', 'is_active'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function products() { return $this->hasMany(Product::class); }

    public function purchases() { return $this->hasMany(Purchase::class)->latest(); }
}
