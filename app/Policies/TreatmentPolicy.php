<?php

namespace App\Policies;

use App\Models\Treatment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TreatmentPolicy
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
        return $user->hasAnyRole(['dentist', 'assistant']);
    }

    public function view(User $user, Treatment $treatment): bool
    {
        return $user->hasAnyRole(['dentist', 'assistant']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('dentist');
    }

    public function update(User $user, Treatment $treatment): bool
    {
        return $user->hasRole('dentist');
    }

    public function delete(User $user, Treatment $treatment): bool
    {
        return false;
    }
}
