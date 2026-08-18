<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'expense_number', 'expense_date', 'account_id', 'vendor_name',
        'category', 'amount', 'payment_method', 'payment_status', 'is_recurring',
        'recurrence_frequency', 'next_recurrence_date', 'description', 'receipt_file', 'created_by',
        'source_type', 'source_id', 'project_id', 'tax',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'next_recurrence_date' => 'date',
            'amount' => 'float',
            'tax' => 'float',
            'is_recurring' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
