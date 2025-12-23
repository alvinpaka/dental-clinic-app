<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class ClinicScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply scope if there's an authenticated user
        if (Auth::check() && Auth::user()->clinic_id) {
            $builder->where($model->getTable() . '.clinic_id', Auth::user()->clinic_id);
        }
    }
}
