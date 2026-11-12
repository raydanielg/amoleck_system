<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageEvent extends Model
{
    protected $fillable = ['message_id', 'status', 'detail'];
}
