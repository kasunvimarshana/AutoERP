<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasPermissionTo('view-users');
    }

    public function view(User $authUser, User $targetUser): bool
    {
        return $authUser->tenant_id === $targetUser->tenant_id
            && ($authUser->id === $targetUser->id || $authUser->hasPermissionTo('view-users'));
    }

    public function update(User $authUser, User $targetUser): bool
    {
        return $authUser->tenant_id === $targetUser->tenant_id
            && ($authUser->id === $targetUser->id || $authUser->hasPermissionTo('update-users'));
    }

    public function delete(User $authUser, User $targetUser): bool
    {
        return $authUser->tenant_id === $targetUser->tenant_id
            && $authUser->hasPermissionTo('delete-users');
    }
}
