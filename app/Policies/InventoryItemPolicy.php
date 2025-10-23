<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InventoryItemPolicy
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

        // Dentists and receptionists can only view inventory
        if (in_array($ability, ['viewAny', 'view'])) {
            return null; // Let the specific methods handle these
        }

        // All other actions require assistant or admin
        if (!in_array($ability, ['viewAny', 'view']) && !$user->hasRole('assistant')) {
            return false;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'assistant', 'receptionist', 'dentist']);
    }

    public function view(User $user, InventoryItem $inventoryItem): bool
    {
        // All authenticated users can view inventory items
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'assistant']);
    }

    public function update(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->hasAnyRole(['admin', 'assistant']);
    }

    public function delete(User $user, InventoryItem $inventoryItem): bool
    {
        // Only admin can delete to prevent accidental data loss
        return $user->hasRole('admin');
    }

    public function restore(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->hasRole('admin');
    }
}