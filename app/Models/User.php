<?php

namespace App\Models;

use App\Models\CanteenMeal;
use App\Models\Department;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'company_id', 'department_id', 'name', 'email', 'password', 'role', 'status',
        'modules', 'must_declare_meals',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'modules' => 'array',
            'must_declare_meals' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function meals(): HasMany
    {
        return $this->hasMany(CanteenMeal::class);
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;

        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }

    public function isFinance(): bool
    {
        return in_array($this->role, ['Admin', 'Finance'], true);
    }

    public function isReviewer(): bool
    {
        return $this->canAccess('canteen.review');
    }

    public function canAccess(string $module): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $allowed = is_array($this->modules) && count($this->modules)
            ? $this->modules
            : (config('modules.roles.'.$this->role) ?? config('modules.roles.Staff'));

        return in_array('*', $allowed, true) || in_array($module, $allowed, true);
    }

    public function allowedModules(): array
    {
        if ($this->isAdmin()) {
            return array_keys(config('modules.catalog', []));
        }

        return is_array($this->modules) && count($this->modules)
            ? $this->modules
            : (config('modules.roles.'.$this->role) ?? ['dashboard', 'canteen']);
    }

    public function seesOnlyOwnRecords(): bool
    {
        return ! $this->canAccess('canteen.review')
            && ! $this->canAccess('requisitions.review')
            && ! $this->isFinance();
    }

    public function todaysMeal(): ?CanteenMeal
    {
        return CanteenMeal::query()
            ->where('user_id', $this->id)
            ->whereDate('meal_date', now()->toDateString())
            ->first();
    }

    public function initial(): string
    {
        return strtoupper(substr($this->name, 0, 1));
    }
}
