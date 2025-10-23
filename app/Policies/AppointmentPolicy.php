<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AppointmentPolicy
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

        // Only receptionists can manage appointments
        if (!$user->hasRole('receptionist')) {
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
    public function view(User $user, Appointment $appointment): bool
    {
        // Dentists can only view their own appointments
        if ($user->hasRole('dentist')) {
            return $appointment->dentist_id === $user->id;
        }
        
        // Assistants can view appointments they're assigned to
        if ($user->hasRole('assistant')) {
            return $appointment->assistant_id === $user->id;
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
    public function update(User $user, Appointment $appointment): bool
    {
        // Only the assigned receptionist or admin can update
        return $appointment->created_by === $user->id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        // Only admin or the creator can delete
        return $appointment->created_by === $user->id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('admin');
    }
}
