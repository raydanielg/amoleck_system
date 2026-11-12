<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FulfilmentEvent extends Model
{
    protected $fillable = ['fulfilment_type', 'fulfilment_id', 'step', 'notes', 'user_id'];

    public function user() { return $this->belongsTo(User::class); }
}
