<?php

namespace App\Policies;

use App\Models\User;
use App\Models\CashSession;

class CashSessionPolicy
{
    public function before(?User $user, $ability)
    {
        // Allow admins everything in this policy
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $this->isCashier($user);
    }

    public function create(User $user): bool
    {
        return $this->isCashier($user);
    }

    public function update(User $user, ?CashSession $session = null): bool
    {
        return $this->isCashier($user);
    }

    protected function isCashier(User $user): bool
    {
        return method_exists($user, 'hasRole') && ($user->hasRole('receptionist') || $user->hasRole('admin'));
    }
}
