<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AttendanceDevice extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'serial_number', 'device_key', 'vendor', 'location',
        'work_start', 'work_end', 'late_grace_minutes', 'is_active', 'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'late_grace_minutes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $device) {
            if (empty($device->device_key)) {
                $device->device_key = Str::random(40);
            }
        });
    }

    public function punches(): HasMany
    {
        return $this->hasMany(AttendancePunch::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
