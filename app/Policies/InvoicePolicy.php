<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
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
        return $user->hasAnyRole(['receptionist']);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(['receptionist']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('receptionist');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('receptionist');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('receptionist');
    }
}
