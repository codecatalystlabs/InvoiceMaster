<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_run_id', 'employee_id', 'basic', 'allowances', 'gross', 'paye',
        'nssf_employee', 'nssf_employer', 'lst', 'canteen', 'other_deductions',
        'net', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'basic' => 'float',
            'allowances' => 'float',
            'gross' => 'float',
            'paye' => 'float',
            'nssf_employee' => 'float',
            'nssf_employer' => 'float',
            'lst' => 'float',
            'canteen' => 'float',
            'other_deductions' => 'float',
            'net' => 'float',
            'meta' => 'array',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
