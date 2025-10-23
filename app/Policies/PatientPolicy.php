<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PatientPolicy
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

        // Only receptionists can manage patients
        if (!in_array($ability, ['viewAny', 'view']) && !$user->hasRole('receptionist')) {
            return false;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'receptionist', 'dentist', 'assistant']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Patient $patient): bool
    {
        // Dentists can only view their own patients
        if ($user->hasRole('dentist')) {
            return $patient->appointments()
                ->where('dentist_id', $user->id)
                ->exists();
        }
        
        // Assistants can view all patients (since there's no assistant_id in appointments)
        if ($user->hasRole('assistant')) {
            return true;
        }

        return $user->hasAnyRole(['admin', 'receptionist']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'receptionist']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Patient $patient): bool
    {
        // Only receptionists and admins can update patient records
        return $user->hasAnyRole(['admin', 'receptionist']);
    }

    /**
     * Determine whether the user can delete the model.
     * 
     * Note: Patient records should not be deleted to maintain data integrity.
     * Instead, consider soft deleting or marking as inactive.
     */
    public function delete(User $user, Patient $patient): bool
    {
        // Only admin can delete, and only if patient has no appointments or invoices
        return $user->hasRole('admin') && 
               $patient->appointments()->count() === 0 &&
               $patient->invoices()->count() === 0;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Patient $patient): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Patient $patient): bool
    {
        // Only admin can force delete, and only if patient has no related records
        return $user->hasRole('admin') && 
               $patient->appointments()->withTrashed()->count() === 0 &&
               $patient->invoices()->withTrashed()->count() === 0;
    }
}
