<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentPermission extends Model
{
    protected $guarded = [];

    protected $casts = ['caps'=>'array','deny'=>'boolean'];
    public function permissible(){return $this->morphTo();}
}
