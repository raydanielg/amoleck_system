<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseApproval extends Model
{
    public const DECISION_APPROVED = 'approved';
    public const DECISION_REJECTED = 'rejected';
    public const DECISION_RETURNED = 'returned';
    public const DECISION_MARKED_PAID = 'marked_paid';
    public const DECISION_RESUBMITTED = 'resubmitted';

    protected $fillable = [
        'expense_id',
        'reviewer_id',
        'decision',
        'reason',
        'previous_status',
        'new_status',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
