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
        'company_id', 'user_id', 'department_id', 'division_id', 'position_id', 'supervisor_id', 'number', 'name', 'gender',
        'date_of_birth', 'national_id', 'email', 'phone', 'address', 'tin', 'nssf_number',
        'next_of_kin', 'next_of_kin_phone', 'job_title', 'employment_type', 'start_date',
        'end_date', 'basic_salary', 'allowances', 'pay_method', 'bank_name', 'pay_account',
        'machine_pin', 'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'date_of_birth' => 'date',
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

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supervisor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function punches(): HasMany
    {
        return $this->hasMany(AttendancePunch::class);
    }

    public function attendanceDays(): HasMany
    {
        return $this->hasMany(AttendanceDay::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(self::class, 'supervisor_id');
    }

    public function gross(): float
    {
        return (float) $this->basic_salary + (float) $this->allowances;
    }

    public function pin(): string
    {
        return $this->machine_pin ?: (string) preg_replace('/\D+/', '', (string) $this->number) ?: (string) $this->id;
    }
}
