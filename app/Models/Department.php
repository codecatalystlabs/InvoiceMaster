<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'code', 'head_user_id', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(AnnualBudget::class);
    }

    public function pettyCashFunds(): HasMany
    {
        return $this->hasMany(PettyCashFund::class);
    }

    public static function seedDefaults(int $companyId): void
    {
        foreach ([
            ['OPS', 'Operations'],
            ['FIN', 'Finance'],
            ['ENG', 'Engineering'],
            ['ADM', 'Administration'],
        ] as [$code, $name]) {
            static::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $companyId, 'code' => $code],
                ['name' => $name, 'is_active' => true]
            );
        }
    }
}
