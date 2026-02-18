<?php

declare(strict_types=1);

namespace Modules\Accounting\Policies;

use App\Policies\BasePolicy;
use Modules\Accounting\Entities\JournalEntry;

/**
 * Journal Entry Policy
 *
 * Authorization policy for journal entry management.
 * Handles CRUD operations and custom abilities like posting and reversing entries.
 */
class JournalEntryPolicy extends BasePolicy
{
    /**
     * Permission prefix for journal entry operations.
     */
    protected string $permissionPrefix = 'journal-entry';

    /**
     * Determine whether the user can post the journal entry.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function post($user, JournalEntry $journalEntry): bool
    {
        return $this->checkPermission($user, 'post') &&
               $this->checkTenantIsolation($user, $journalEntry) &&
               $journalEntry->status === 'draft' &&
               ($this->hasAnyRole($user, ['admin', 'finance-manager', 'accountant']));
    }

    /**
     * Determine whether the user can reverse the journal entry.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function reverse($user, JournalEntry $journalEntry): bool
    {
        return $this->checkPermission($user, 'reverse') &&
               $this->checkTenantIsolation($user, $journalEntry) &&
               $journalEntry->status === 'posted' &&
               ! $journalEntry->is_reversed &&
               ($this->hasAnyRole($user, ['admin', 'finance-manager']));
    }

    /**
     * Determine whether the user can approve the journal entry.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function approve($user, JournalEntry $journalEntry): bool
    {
        return $this->checkPermission($user, 'approve') &&
               $this->checkTenantIsolation($user, $journalEntry) &&
               $journalEntry->status === 'pending' &&
               ! $this->isOwner($user, $journalEntry) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can reject the journal entry.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function reject($user, JournalEntry $journalEntry): bool
    {
        return $this->checkPermission($user, 'reject') &&
               $this->checkTenantIsolation($user, $journalEntry) &&
               $journalEntry->status === 'pending' &&
               ! $this->isOwner($user, $journalEntry) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can submit the journal entry for approval.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function submit($user, JournalEntry $journalEntry): bool
    {
        return $this->checkPermission($user, 'submit') &&
               $this->checkTenantIsolation($user, $journalEntry) &&
               $journalEntry->status === 'draft' &&
               ($this->isOwner($user, $journalEntry) || $this->hasAnyRole($user, ['admin', 'finance-manager', 'accountant']));
    }

    /**
     * Determine whether the user can view the journal entry balance.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewBalance($user, JournalEntry $journalEntry): bool
    {
        return $this->checkPermission($user, 'view-balance') &&
               $this->checkTenantIsolation($user, $journalEntry);
    }

    /**
     * Determine whether the user can close a fiscal period containing this entry.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function closePeriod($user, JournalEntry $journalEntry): bool
    {
        return $this->checkPermission($user, 'close-period') &&
               $this->checkTenantIsolation($user, $journalEntry) &&
               $this->hasAnyRole($user, ['admin', 'super-admin']);
    }

    /**
     * Determine whether the user can delete the journal entry (override to add status check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function delete($user, JournalEntry $journalEntry): bool
    {
        return parent::delete($user, $journalEntry) &&
               in_array($journalEntry->status, ['draft', 'rejected']);
    }

    /**
     * Determine whether the user can update the journal entry (override to add status check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function update($user, JournalEntry $journalEntry): bool
    {
        return parent::update($user, $journalEntry) &&
               in_array($journalEntry->status, ['draft', 'rejected']);
    }
}
