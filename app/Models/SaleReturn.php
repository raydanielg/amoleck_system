<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    protected $fillable = ['return_reference', 'sale_id', 'reason', 'recorded_by'];

    public function sale() { return $this->belongsTo(Sale::class); }

    public function items() { return $this->hasMany(SaleReturnItem::class); }

    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
}
