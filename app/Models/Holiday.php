<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'holiday_date'];

    protected function casts(): array
    {
        return ['holiday_date' => 'date'];
    }

    public static function seedDefaults(int $companyId): void
    {
        $years = [(int) now()->year, (int) now()->year + 1];
        $easter = [
            2026 => ['2026-04-03', '2026-04-06'],
            2027 => ['2027-03-26', '2027-03-29'],
            2028 => ['2028-04-14', '2028-04-17'],
        ];
        foreach ($years as $year) {
            $days = [
                [$year.'-01-01', "New Year's Day"],
                [$year.'-01-26', 'NRM Liberation Day'],
                [$year.'-02-16', 'Archbishop Janani Luwum Day'],
                [$year.'-03-08', "International Women's Day"],
                [$year.'-05-01', 'Labour Day'],
                [$year.'-06-03', 'Uganda Martyrs Day'],
                [$year.'-06-09', 'National Heroes Day'],
                [$year.'-10-09', 'Independence Day'],
                [$year.'-12-25', 'Christmas Day'],
                [$year.'-12-26', 'Boxing Day'],
            ];
            if (isset($easter[$year])) {
                $days[] = [$easter[$year][0], 'Good Friday'];
                $days[] = [$easter[$year][1], 'Easter Monday'];
            }
            foreach ($days as [$date, $name]) {
                static::withoutGlobalScopes()->firstOrCreate(
                    ['company_id' => $companyId, 'holiday_date' => $date],
                    ['name' => $name]
                );
            }
        }
    }
}
