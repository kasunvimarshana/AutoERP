<?php

declare(strict_types=1);

namespace Modules\Accounting\Policies;

use App\Policies\BasePolicy;
use Modules\Accounting\Entities\Account;

/**
 * Account Policy
 *
 * Authorization policy for chart of accounts management.
 * Handles CRUD operations and custom abilities like marking system accounts.
 */
class AccountPolicy extends BasePolicy
{
    /**
     * Permission prefix for account operations.
     */
    protected string $permissionPrefix = 'account';

    /**
     * Determine whether the user can mark the account as a system account.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function markSystemAccount($user, Account $account): bool
    {
        return $this->checkPermission($user, 'mark-system-account') &&
               $this->checkTenantIsolation($user, $account) &&
               $this->hasAnyRole($user, ['admin', 'super-admin']);
    }

    /**
     * Determine whether the user can activate the account.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function activate($user, Account $account): bool
    {
        return $this->checkPermission($user, 'activate') &&
               $this->checkTenantIsolation($user, $account) &&
               ($this->hasAnyRole($user, ['admin', 'finance-manager']));
    }

    /**
     * Determine whether the user can deactivate the account.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function deactivate($user, Account $account): bool
    {
        return $this->checkPermission($user, 'deactivate') &&
               $this->checkTenantIsolation($user, $account) &&
               ! $account->is_system &&
               ($this->hasAnyRole($user, ['admin', 'finance-manager']));
    }

    /**
     * Determine whether the user can reconcile the account.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function reconcile($user, Account $account): bool
    {
        return $this->checkPermission($user, 'reconcile') &&
               $this->checkTenantIsolation($user, $account) &&
               ($this->hasAnyRole($user, ['admin', 'finance-manager', 'accountant']));
    }

    /**
     * Determine whether the user can view account balance.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewBalance($user, Account $account): bool
    {
        return $this->checkPermission($user, 'view-balance') &&
               $this->checkTenantIsolation($user, $account);
    }

    /**
     * Determine whether the user can view account transactions.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewTransactions($user, Account $account): bool
    {
        return $this->checkPermission($user, 'view-transactions') &&
               $this->checkTenantIsolation($user, $account);
    }

    /**
     * Determine whether the user can delete the account (override to add system check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function delete($user, Account $account): bool
    {
        return parent::delete($user, $account) &&
               ! $account->is_system;
    }

    /**
     * Determine whether the user can update the account (override to add system check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function update($user, Account $account): bool
    {
        // System accounts can only be updated by super-admin
        if ($account->is_system && ! $user->hasRole('super-admin')) {
            return false;
        }

        return parent::update($user, $account);
    }
}
