<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['quotation_id', 'item_name', 'qty', 'unit_price', 'total'];

    protected function casts(): array
    {
        return ['qty' => 'float', 'unit_price' => 'float', 'total' => 'float'];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}
