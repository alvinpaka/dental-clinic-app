<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppointmentPolicy
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

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->hasAnyRole(['receptionist', 'dentist', 'assistant']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('receptionist');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('receptionist');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('receptionist');
    }
}
