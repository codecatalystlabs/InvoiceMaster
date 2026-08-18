<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanteenMealLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'canteen_meal_id', 'canteen_item_id', 'item_name', 'item_type',
        'qty', 'unit_price', 'line_total',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'float',
            'unit_price' => 'float',
            'line_total' => 'float',
        ];
    }

    public function meal(): BelongsTo
    {
        return $this->belongsTo(CanteenMeal::class, 'canteen_meal_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(CanteenItem::class, 'canteen_item_id');
    }
}
