<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InventoryItemPolicy
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
        return $user->hasAnyRole(['assistant', 'receptionist']);
    }

    public function view(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->hasAnyRole(['assistant', 'receptionist']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('assistant');
    }

    public function update(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->hasRole('assistant');
    }

    public function delete(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->hasRole('assistant');
    }
}
