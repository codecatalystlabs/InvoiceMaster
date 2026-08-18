<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class DocumentNumber
{
    public static function next(string $prefix, string $table, string $column, int $companyId): string
    {
        $year = date('Y');
        $like = $prefix.'-'.$year.'-%';

        $last = DB::table($table)
            ->where('company_id', $companyId)
            ->where($column, 'like', $like)
            ->orderByDesc('id')
            ->value($column);

        $n = 1;
        if ($last) {
            $n = ((int) substr($last, -4)) + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $n);
    }
}
