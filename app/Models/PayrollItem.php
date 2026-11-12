<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    protected $guarded = [];

protected $casts = ["deductions"=>"array","employer_contributions"=>"array","basic"=>"decimal:2","allowances"=>"decimal:2","gross"=>"decimal:2","total_deductions"=>"decimal:2","net"=>"decimal:2","total_employer"=>"decimal:2","paid"=>"decimal:2"];
    public function batch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\PayrollBatch::class, "payroll_batch_id");
    }
    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Employee::class);
    }
    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\PayrollPayment::class);
    }

}
