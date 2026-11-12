<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLoan extends Model
{
    protected $guarded = [];

protected $casts = ["amount"=>"decimal:2","monthly_installment"=>"decimal:2","balance"=>"decimal:2","issued_at"=>"date"];
    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Employee::class);
    }
    public function repayments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\LoanRepayment::class);
    }

}
