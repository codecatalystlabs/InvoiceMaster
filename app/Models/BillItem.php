<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['bill_id', 'item_name', 'qty', 'unit_price', 'total'];

    protected function casts(): array
    {
        return ['qty' => 'float', 'unit_price' => 'float', 'total' => 'float'];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
