<?php

declare(strict_types=1);

namespace Modules\Accounting\Policies;

use Modules\Accounting\Models\Account;
use Modules\Auth\Models\User;

class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('accounts.view');
    }

    public function view(User $user, Account $account): bool
    {
        return $user->hasPermission('accounts.view')
            && $user->tenant_id === $account->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('accounts.create');
    }

    public function update(User $user, Account $account): bool
    {
        return $user->hasPermission('accounts.update')
            && $user->tenant_id === $account->tenant_id;
    }

    public function delete(User $user, Account $account): bool
    {
        return $user->hasPermission('accounts.delete')
            && $user->tenant_id === $account->tenant_id;
    }

    public function restore(User $user, Account $account): bool
    {
        return $user->hasPermission('accounts.delete')
            && $user->tenant_id === $account->tenant_id;
    }

    public function forceDelete(User $user, Account $account): bool
    {
        return $user->hasPermission('accounts.force_delete')
            && $user->tenant_id === $account->tenant_id;
    }
}
