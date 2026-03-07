<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasAnyRole(['admin', 'manager']);
    }

    public function view(User $authUser, User $user): bool
    {
        // Admin/manager can view any; staff can only view themselves
        return $authUser->hasAnyRole(['admin', 'manager']) || $authUser->id === $user->id;
    }

    public function create(User $authUser): bool
    {
        return $authUser->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $authUser, User $user): bool
    {
        // Admins can update anyone; managers can update staff; staff can update themselves
        if ($authUser->hasRole('admin')) return true;
        if ($authUser->hasRole('manager') && $user->hasRole('staff')) return true;
        return $authUser->id === $user->id;
    }

    public function delete(User $authUser, User $user): bool
    {
        return $authUser->hasRole('admin') && $authUser->id !== $user->id;
    }

    public function manageRoles(User $authUser, User $user): bool
    {
        return $authUser->hasRole('admin');
    }

    public function manageAttributes(User $authUser, User $user): bool
    {
        return $authUser->hasAnyRole(['admin', 'manager']);
    }
}
