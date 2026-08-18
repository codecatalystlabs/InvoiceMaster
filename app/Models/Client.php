<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'email', 'phone', 'company', 'address', 'portal_token'];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function portalUrl(): string
    {
        if (! $this->portal_token) {
            $this->portal_token = \Illuminate\Support\Str::random(48);
            $this->saveQuietly();
        }

        return url('portal/'.$this->portal_token);
    }
}
