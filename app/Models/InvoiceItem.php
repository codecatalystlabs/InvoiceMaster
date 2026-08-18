<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['invoice_id', 'item_name', 'qty', 'unit_price', 'total'];

    protected function casts(): array
    {
        return ['qty' => 'float', 'unit_price' => 'float', 'total' => 'float'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
