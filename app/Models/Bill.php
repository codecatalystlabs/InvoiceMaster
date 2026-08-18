<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'service_id', 'project_id', 'account_id', 'number', 'vendor_name',
        'bill_date', 'due_date', 'subtotal', 'tax', 'total', 'amount_paid', 'status',
        'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'bill_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'float',
            'tax' => 'float',
            'total' => 'float',
            'amount_paid' => 'float',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function outstanding(): float
    {
        return max(0, (float) $this->total - (float) $this->amount_paid);
    }
}
