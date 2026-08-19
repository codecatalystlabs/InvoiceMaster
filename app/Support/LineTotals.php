<?php

namespace App\Support;

class LineTotals
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{items: array<int, array{item_name: string, name: string, qty: float, unit_price: float, total: float}>, subtotal: float, tax: float, total: float}
     */
    public static function compute(array $items, float $taxRate = 0, float $discount = 0): array
    {
        $clean = [];
        $subtotal = 0.0;

        foreach ($items as $item) {
            $name = trim((string) ($item['item_name'] ?? $item['description'] ?? ''));
            $qty = (float) ($item['qty'] ?? $item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            if ($name === '' || $qty <= 0 || $price < 0) {
                continue;
            }
            $line = round($qty * $price, 2);
            $subtotal += $line;
            $clean[] = [
                'item_name' => $name,
                'name' => $name,
                'qty' => $qty,
                'unit_price' => $price,
                'total' => $line,
            ];
        }

        $tax = round(($subtotal * $taxRate) / 100, 2);
        $total = round($subtotal + $tax - $discount, 2);

        return [
            'items' => $clean,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ];
    }
}
