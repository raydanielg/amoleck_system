<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    protected $guarded = [];

protected $casts = ["amount"=>"decimal:2","paid_at"=>"date"];
    public function loan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\EmployeeLoan::class, "employee_loan_id");
    }

}
