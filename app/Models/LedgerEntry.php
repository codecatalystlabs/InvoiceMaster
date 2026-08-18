<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'entry_date', 'reference_number', 'account_id', 'entry_type',
        'amount', 'description', 'source_type', 'source_id', 'created_by',
    ];

    protected function casts(): array
    {
        return ['entry_date' => 'date', 'amount' => 'float'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
