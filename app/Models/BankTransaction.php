<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    protected $guarded = [];

protected $casts = ["date"=>"date","amount"=>"decimal:2"];
    public function account(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\FinancialAccount::class, "financial_account_id");
    }

}
