<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExpensePolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Admin has all permissions
        if ($user->hasRole('admin')) {
            return true;
        }

        // Dentists can only view expenses
        if ($ability === 'viewAny' || $ability === 'view') {
            return null; // Let the specific methods handle these
        }

        // All other actions require admin or receptionist
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
    public function view(User $user, Expense $expense): bool
    {
        // Dentists can only view their own expenses or clinic-wide expenses
        if ($user->hasRole('dentist')) {
            return $expense->created_by === $user->id || $expense->is_clinic_expense;
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
    public function update(User $user, Expense $expense): bool
    {
        // Only the creator or admin can update
        return $expense->created_by === $user->id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Expense $expense): bool
    {
        // Only admin can delete expenses
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Expense $expense): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Expense $expense): bool
    {
        return $user->hasRole('admin');
    }
}