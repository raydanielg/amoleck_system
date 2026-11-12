<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $guarded = [];

    protected $casts = ['targets'=>'array','pinned'=>'boolean','requires_ack'=>'boolean','published_at'=>'datetime','scheduled_at'=>'datetime','expires_at'=>'datetime'];
    public function attachments(){return $this->belongsToMany(Document::class,'announcement_attachments');}
    public function receipts(){return $this->hasMany(AnnouncementReceipt::class);}
    public function publisher(){return $this->belongsTo(User::class,'published_by');}
    public function creator(){return $this->belongsTo(User::class,'created_by');}
    public function scopeLive($q){return $q->where('status','published')->where(fn($w)=>$w->whereNull('expires_at')->orWhere('expires_at','>',now()));}
}
