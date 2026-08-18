<?php

namespace App\Support;

class PayrollTax
{
    /**
     * Uganda PAYE monthly bands (URA). Update when the budget changes.
     */
    public static function paye(float $chargeable): float
    {
        $chargeable = max(0, $chargeable);
        $tax = 0.0;
        $bands = [
            [235000, 0.00],
            [100000, 0.10],
            [75000, 0.20],
            [9590000, 0.30],
            [PHP_INT_MAX, 0.40],
        ];
        $remaining = $chargeable;
        foreach ($bands as [$width, $rate]) {
            $slice = min($remaining, $width);
            $tax += $slice * $rate;
            $remaining -= $slice;
            if ($remaining <= 0) {
                break;
            }
        }

        return round($tax, 0);
    }

    public static function nssfEmployee(float $gross, float $ceiling = 0): float
    {
        $base = $ceiling > 0 ? min($gross, $ceiling) : $gross;

        return round($base * 0.05, 0);
    }

    public static function nssfEmployer(float $gross, float $ceiling = 0): float
    {
        $base = $ceiling > 0 ? min($gross, $ceiling) : $gross;

        return round($base * 0.10, 0);
    }

    public static function lst(float $gross): float
    {
        if ($gross < 100000) {
            return 0;
        }
        if ($gross < 200000) {
            return 5000;
        }
        if ($gross < 300000) {
            return 8000;
        }

        return 10000;
    }
}
