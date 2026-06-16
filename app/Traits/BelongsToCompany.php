<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        // Global scope: filter all queries by current company
        static::addGlobalScope('company', function (Builder $builder) {
            $companyId = app()->bound('current_company_id')
                ? app('current_company_id')
                : null;

            if ($companyId !== null) {
                $builder->where((new static)->getTable() . '.company_id', $companyId);
            }
            // null = not yet bound (unauthenticated / login flow) → no filter
        });

        // Auto-fill company_id on create
        static::creating(function ($model) {
            if (empty($model->company_id)) {
                $companyId = app()->bound('current_company_id')
                    ? app('current_company_id')
                    : null;

                if ($companyId !== null) {
                    $model->company_id = $companyId;
                }
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}
