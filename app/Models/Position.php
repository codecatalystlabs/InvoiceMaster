<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'department_id', 'division_id', 'name', 'code', 'level', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function levelLabel(): string
    {
        return match ($this->level) {
            'intern' => 'Intern',
            'junior' => 'Junior',
            'mid' => 'Mid',
            'senior' => 'Senior',
            'lead' => 'Lead',
            'manager' => 'Manager',
            'director' => 'Director',
            'executive' => 'Executive',
            default => ucfirst((string) $this->level),
        };
    }
}
