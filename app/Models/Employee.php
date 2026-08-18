<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'user_id', 'department_id', 'number', 'name', 'email', 'phone',
        'tin', 'nssf_number', 'job_title', 'start_date', 'basic_salary', 'allowances',
        'pay_method', 'pay_account', 'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'basic_salary' => 'float',
            'allowances' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function gross(): float
    {
        return (float) $this->basic_salary + (float) $this->allowances;
    }
}
