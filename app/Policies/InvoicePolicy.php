<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class InvoicePolicy
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

        // Only receptionists and admins can manage invoices
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
        return $user->hasAnyRole(['admin', 'receptionist', 'dentist']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // Dentists can only view their own invoices
        if ($user->hasRole('dentist')) {
            return $invoice->appointment->dentist_id === $user->id;
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
    public function update(User $user, Invoice $invoice): bool
    {
        // Admin or receptionist can update any invoice; creator can update their own
        return $user->hasAnyRole(['admin', 'receptionist']) || $invoice->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // Only admin can delete to maintain financial integrity
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('admin');
    }
}
