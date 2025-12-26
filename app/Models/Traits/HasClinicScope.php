<?php

namespace App\Models\Traits;

use App\Models\Scopes\ClinicScope;
use Illuminate\Database\Eloquent\Model;

trait HasClinicScope
{
    /**
     * Boot the trait.
     */
    protected static function bootHasClinicScope(): void
    {
        static::addGlobalScope(new ClinicScope);
        
        // Automatically set clinic_id when creating records
        static::creating(function (Model $model) {
            if (auth()->check() && auth()->user()->clinic_id && !$model->clinic_id) {
                $model->clinic_id = auth()->user()->clinic_id;
            }
        });
    }

    /**
     * Get the clinic_id for the model.
     */
    public function getClinicIdAttribute(): ?int
    {
        return $this->attributes['clinic_id'] ?? null;
    }

    /**
     * Scope a query to only include records for a specific clinic.
     */
    public function scopeForClinic($query, $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    /**
     * Scope a query to include all records (bypass clinic scope).
     */
    public function scopeWithoutClinicScope($query)
    {
        return $query->withoutGlobalScope(ClinicScope::class);
    }
}
