<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyCashEntry extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'petty_cash_fund_id', 'requisition_id', 'budget_allocation_id', 'number',
        'entry_date', 'type', 'description', 'amount', 'balance_after', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount' => 'float',
            'balance_after' => 'float',
        ];
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(PettyCashFund::class, 'petty_cash_fund_id');
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public static function types(): array
    {
        return [
            'allocation' => 'Allocation / top-up',
            'issue' => 'Issue to staff',
            'spend' => 'Accounted spend',
            'return' => 'Cash returned',
            'replenish' => 'Replenish',
        ];
    }
}
