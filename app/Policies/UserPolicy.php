<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability)
    {
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        // Allow users to view staff if they have a clinic assigned
        return $user->clinic_id !== null || $user->hasRole('super-admin');
    }

    public function view(User $user, User $model): bool
    {
        // Super admins can view any user
        if ($user->hasRole('super-admin')) {
            return true;
        }
        
        // Users can view staff from their own clinic
        return $user->clinic_id === $model->clinic_id;
    }

    public function create(User $user): bool
    {
        // Admins and super-admins can create staff
        return $user->hasRole('admin') || $user->hasRole('super-admin');
    }

    public function update(User $user, User $model): bool
    {
        // Super admins can update any user
        if ($user->hasRole('super-admin')) {
            return true;
        }
        
        // Admins can update staff from their clinic
        if ($user->hasRole('admin') && $user->clinic_id === $model->clinic_id) {
            return true;
        }
        
        return false;
    }

    public function delete(User $user, User $model): bool
    {
        // Super admins can delete any user
        if ($user->hasRole('super-admin')) {
            return true;
        }
        
        // Admins can delete staff from their clinic (but not themselves)
        if ($user->hasRole('admin') && $user->clinic_id === $model->clinic_id && $user->id !== $model->id) {
            return true;
        }
        
        return false;
    }
}
