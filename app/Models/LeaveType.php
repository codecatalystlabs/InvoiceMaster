<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'code', 'paid', 'days_per_year', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'paid' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public static function seedDefaults(int $companyId): void
    {
        foreach ([
            ['ANNUAL', 'Annual leave', true, 21],
            ['SICK', 'Sick leave', true, 7],
            ['MATERNITY', 'Maternity leave', true, 60],
            ['PATERNITY', 'Paternity leave', true, 4],
            ['COMPASSION', 'Compassionate', true, 5],
            ['UNPAID', 'Unpaid leave', false, 0],
        ] as [$code, $name, $paid, $days]) {
            static::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $companyId, 'code' => $code],
                ['name' => $name, 'paid' => $paid, 'days_per_year' => $days, 'is_active' => true]
            );
        }
    }

    public static function seedBalanceFor(Employee $employee, ?int $year = null): void
    {
        $year = $year ?: (int) now()->year;
        $types = static::withoutGlobalScopes()
            ->where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->get();
        foreach ($types as $type) {
            LeaveBalance::withoutGlobalScopes()->firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'leave_type_id' => $type->id,
                    'year' => $year,
                ],
                [
                    'company_id' => $employee->company_id,
                    'entitled' => $type->days_per_year,
                    'taken' => 0,
                ]
            );
        }
    }
}
