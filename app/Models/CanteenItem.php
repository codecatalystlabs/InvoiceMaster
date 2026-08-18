<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanteenItem extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'type', 'unit', 'price', 'is_priced', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'is_priced' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CanteenMealLine::class);
    }

    public static function types(): array
    {
        return [
            'sauce' => 'Source (priced)',
            'food' => 'Food served with source',
            'drink' => 'Drink',
            'extra' => 'Extra (priced)',
        ];
    }

    public static function seedDefaults(int $companyId): void
    {
        $items = [
            ['Posho', 'food', 'plate', 0, 10, false],
            ['Rice', 'food', 'plate', 0, 20, false],
            ['Matooke', 'food', 'plate', 0, 30, false],
            ['Chapati', 'food', 'piece', 0, 40, false],
            ['Irish potatoes', 'food', 'plate', 0, 50, false],
            ['Cassava', 'food', 'plate', 0, 60, false],
            ['Sweet potatoes', 'food', 'plate', 0, 70, false],
            ['Greens (dodo)', 'food', 'serving', 0, 80, false],
            ['Cabbage', 'food', 'serving', 0, 90, false],
            ['Mixed vegetables', 'food', 'serving', 0, 100, false],
            ['Beans', 'sauce', 'serving', 2000, 110, true],
            ['Groundnut sauce', 'sauce', 'serving', 2500, 120, true],
            ['Beef stew', 'sauce', 'serving', 6000, 130, true],
            ['Chicken stew', 'sauce', 'serving', 7000, 140, true],
            ['Fish stew', 'sauce', 'serving', 6000, 150, true],
            ['Tea', 'drink', 'cup', 1000, 210, true],
            ['African tea', 'drink', 'cup', 1500, 220, true],
            ['Coffee', 'drink', 'cup', 1500, 230, true],
            ['Drinking water', 'drink', 'bottle', 500, 240, true],
            ['Soda', 'drink', 'bottle', 2000, 250, true],
            ['Fried egg', 'extra', 'piece', 1500, 310, true],
            ['Avocado', 'extra', 'piece', 1000, 320, true],
            ['Salad', 'extra', 'serving', 1500, 330, true],
            ['Extra sauce', 'extra', 'serving', 1000, 340, true],
        ];

        foreach ($items as [$name, $type, $unit, $price, $sort, $priced]) {
            static::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $companyId, 'name' => $name],
                ['type' => $type, 'unit' => $unit, 'price' => $price, 'is_priced' => $priced, 'is_active' => true, 'sort_order' => $sort]
            );
        }
    }
}
