<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'asset_number', 'asset_name', 'category', 'purchase_date',
        'purchase_price', 'current_value', 'depreciation_rate', 'depreciation_method',
        'location', 'condition_status', 'description', 'serial_number',
        'warranty_expiry', 'assigned_to', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'warranty_expiry' => 'date',
            'purchase_price' => 'float',
            'current_value' => 'float',
            'depreciation_rate' => 'float',
        ];
    }

    public function valuations(): HasMany
    {
        return $this->hasMany(AssetValuation::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
