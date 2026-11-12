<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementReceipt extends Model
{
    protected $guarded = [];

    protected $casts = ['delivered_at'=>'datetime','viewed_at'=>'datetime','acknowledged_at'=>'datetime'];
    public function announcement(){return $this->belongsTo(Announcement::class);}
    public function user(){return $this->belongsTo(User::class);}
}
