<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PettyCashFund extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'department_id', 'name', 'custodian_user_id',
        'float_limit', 'balance', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'float_limit' => 'float',
            'balance' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_user_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(PettyCashEntry::class);
    }

    public function headroom(): float
    {
        return max(0, (float) $this->float_limit - (float) $this->balance);
    }
}
