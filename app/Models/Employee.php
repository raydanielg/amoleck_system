<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $guarded = [];

protected $casts = ["hired_at"=>"date","contract_start"=>"date","contract_end"=>"date","dob"=>"date","basic_salary"=>"decimal:2"];
protected $hidden = ["bank_details"];
    public function getBankDetailsAttribute($v): ?string
    {
        try { return $v ? \Illuminate\Support\Facades\Crypt::decryptString($v) : null; } catch (\Throwable) { return null; }
    }
    public function setBankDetailsAttribute($v): void
    {
        $this->attributes["bank_details"] = $v ? \Illuminate\Support\Facades\Crypt::encryptString($v) : null;
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Department::class);
    }
    public function jobPosition(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\JobPosition::class);
    }
    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }
    public function components(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\SalaryComponent::class);
    }
    public function loans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\EmployeeLoan::class);
    }
    public function leaveRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\LeaveRequest::class);
    }
    public function attendance(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\AttendanceRecord::class);
    }
    public function payrollItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\PayrollItem::class);
    }
    public function assets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Asset::class);
    }

}
