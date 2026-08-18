<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requisition extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'number', 'department_id', 'budget_allocation_id', 'petty_cash_fund_id',
        'user_id', 'title', 'purpose', 'amount', 'type', 'status', 'disbursement_method',
        'initiated_by', 'initiated_at', 'approved_by', 'approved_at',
        'rejected_by', 'rejected_at', 'reject_reason',
        'disbursed_by', 'disbursed_at', 'accounted_at', 'accountability_notes',
        'accounted_amount', 'closed_by', 'closed_at', 'expense_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'accounted_amount' => 'float',
            'initiated_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'disbursed_at' => 'datetime',
            'accounted_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(BudgetAllocation::class, 'budget_allocation_id');
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(PettyCashFund::class, 'petty_cash_fund_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RequisitionStep::class)->latest();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RequisitionLine::class);
    }

    public function remainder(): float
    {
        return max(0, (float) $this->amount - (float) $this->accounted_amount);
    }

    public function usesTin(): bool
    {
        return $this->type === 'petty_cash' || (bool) $this->petty_cash_fund_id;
    }

    public static function reviewerCanAct(\App\Models\User $user, self $req): bool
    {
        if ($user->canAccess('requisitions.review')) {
            return true;
        }

        return (int) $req->department?->head_user_id === (int) $user->id;
    }

    public static function statuses(): array
    {
        return [
            'submitted' => 'Submitted',
            'initiated' => 'Initiated',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'disbursed' => 'Disbursed',
            'accounted' => 'Accountability in',
            'closed' => 'Closed',
        ];
    }
}
