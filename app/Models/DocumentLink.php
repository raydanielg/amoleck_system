<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentLink extends Model
{
    protected $guarded = [];

    protected $casts = ['expires_at'=>'datetime','revoked_at'=>'datetime'];
    protected $hidden = ['password'];
    public function document(){return $this->belongsTo(Document::class);}
    public function isUsable(): bool {
        return !$this->revoked_at && (!$this->expires_at || $this->expires_at->isFuture())
            && ($this->max_access === null || $this->access_count < $this->max_access);
    }
}
