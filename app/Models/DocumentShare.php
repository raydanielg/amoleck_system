<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentShare extends Model
{
    protected $guarded = [];

    protected $casts = ['caps'=>'array','expires_at'=>'datetime','revoked_at'=>'datetime'];
    public function shareable(){return $this->morphTo();}
    public function sharer(){return $this->belongsTo(User::class,'shared_by');}
    public function scopeActive($q){return $q->whereNull('revoked_at')->where(fn($w)=>$w->whereNull('expires_at')->orWhere('expires_at','>',now()));}
}
