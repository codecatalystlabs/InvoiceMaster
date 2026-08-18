<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'service_number', 'service_name', 'provider_name', 'provider_contact',
        'category', 'cost', 'billing_frequency', 'start_date', 'end_date',
        'next_billing_date', 'auto_renew', 'status', 'description', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'next_billing_date' => 'date',
            'cost' => 'float',
            'auto_renew' => 'boolean',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ServicePayment::class);
    }
}
