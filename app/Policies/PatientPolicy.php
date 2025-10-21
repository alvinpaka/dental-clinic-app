<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PatientPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability)
    {
        if ($user->hasRole('admin')) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['receptionist', 'dentist', 'assistant']);
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->hasAnyRole(['receptionist', 'dentist', 'assistant']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('receptionist');
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->hasRole('receptionist');
    }

    public function delete(User $user, Patient $patient): bool
    {
        return false;
    }
}
