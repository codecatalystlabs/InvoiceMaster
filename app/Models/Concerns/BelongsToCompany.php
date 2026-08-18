<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            if (auth()->check() && auth()->user()->company_id) {
                $query->where($query->getModel()->getTable().'.company_id', auth()->user()->company_id);
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && empty($model->company_id)) {
                $model->company_id = auth()->user()->company_id;
            }
            if (auth()->check() && $model->isFillable('created_by') && empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
        });
    }
}
