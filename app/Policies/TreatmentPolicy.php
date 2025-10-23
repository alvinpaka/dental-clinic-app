<?php

namespace App\Policies;

use App\Models\Treatment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TreatmentPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Admin has all permissions
        if ($user->hasRole('admin')) {
            return true;
        }

        // Allow view-related actions to be handled by their respective methods
        if (in_array($ability, ['viewAny', 'view'])) {
            return null;
        }

        // Only dentists can manage treatments
        if (!in_array($ability, ['viewAny', 'view']) && !$user->hasRole('dentist')) {
            return false;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'dentist', 'assistant', 'receptionist']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Treatment $treatment): bool
    {
        // Assistants can view treatments they're assigned to
        if ($user->hasRole('assistant')) {
            return $treatment->appointment->assistant_id === $user->id;
        }
        
        // Receptionists can view all treatments
        if ($user->hasRole('receptionist')) {
            return true;
        }

        // Dentists can view their own treatments
        if ($user->hasRole('dentist')) {
            return $treatment->appointment->dentist_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'dentist']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Treatment $treatment): bool
    {
        // Only the treating dentist or admin can update
        return $treatment->appointment->dentist_id === $user->id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     * 
     * Note: Treatments should generally not be deleted to maintain records.
     * Instead, consider marking as cancelled or completed.
     */
    public function delete(User $user, Treatment $treatment): bool
    {
        // Only admin can delete, and only if not linked to any invoices
        return $user->hasRole('admin') && $treatment->invoices()->count() === 0;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Treatment $treatment): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Treatment $treatment): bool
    {
        // Only admin can force delete, and only if no linked invoices exist
        return $user->hasRole('admin') && $treatment->invoices()->withTrashed()->count() === 0;
    }
}
