<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetValuation extends Model
{
    protected $fillable = ['asset_id', 'valuation_date', 'valuation_amount', 'valuation_reason', 'valued_by'];

    protected function casts(): array
    {
        return ['valuation_date' => 'date', 'valuation_amount' => 'float'];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
