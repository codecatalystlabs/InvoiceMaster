<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'year', 'month', 'number', 'status', 'pay_date', 'gross',
        'paye', 'nssf_employee', 'nssf_employer', 'lst', 'canteen',
        'other_deductions', 'net', 'posted_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'pay_date' => 'date',
            'posted_at' => 'datetime',
            'gross' => 'float',
            'paye' => 'float',
            'nssf_employee' => 'float',
            'nssf_employer' => 'float',
            'lst' => 'float',
            'canteen' => 'float',
            'other_deductions' => 'float',
            'net' => 'float',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function periodLabel(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }
}
