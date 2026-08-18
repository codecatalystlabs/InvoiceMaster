<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashBookEntry extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'number', 'entry_date', 'description', 'folio', 'discount_allowed',
        'type', 'amount', 'balance_after', 'account_id', 'payment_method',
        'invoice_id', 'expense_id', 'service_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount' => 'float',
            'balance_after' => 'float',
            'discount_allowed' => 'float',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
