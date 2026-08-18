<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendancePunch extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'attendance_device_id', 'employee_id', 'machine_pin',
        'punched_at', 'status', 'verify', 'source',
    ];

    protected function casts(): array
    {
        return [
            'punched_at' => 'datetime',
            'status' => 'integer',
            'verify' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'attendance_device_id');
    }

    public function isOut(): bool
    {
        return in_array((int) $this->status, [1, 5], true);
    }
}
