<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'expense_reference',
        'title',
        'expense_category_id',
        'category_name_snapshot',
        'amount',
        'expense_date',
        'description',
        'payee',
        'payment_method',
        'receipt_path',
        'status',
        'rejection_reason',
        'returned_reason',
        'void_reason',
        'recorded_by',
        'reviewed_by',
        'reviewed_at',
        'paid_at',
        'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function approvals()
    {
        return $this->hasMany(ExpenseApproval::class)->latest();
    }

    public function comments()
    {
        return $this->hasMany(ExpenseComment::class)->latest();
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    /** Statuses that count as approved money going out. */
    public static function countableStatuses(): array
    {
        return [self::STATUS_APPROVED, self::STATUS_PAID];
    }
}
