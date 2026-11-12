<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollRule extends Model
{
    protected $guarded = [];

protected $casts = ["value"=>"decimal:4","effective_from"=>"date","effective_to"=>"date","is_active"=>"boolean"];
}
