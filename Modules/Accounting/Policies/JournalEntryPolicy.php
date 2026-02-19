<?php

declare(strict_types=1);

namespace Modules\Accounting\Policies;

use Modules\Accounting\Models\JournalEntry;
use Modules\Auth\Models\User;

class JournalEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('journal_entries.view');
    }

    public function view(User $user, JournalEntry $journalEntry): bool
    {
        return $user->hasPermission('journal_entries.view')
            && $user->tenant_id === $journalEntry->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('journal_entries.create');
    }

    public function update(User $user, JournalEntry $journalEntry): bool
    {
        return $user->hasPermission('journal_entries.update')
            && $user->tenant_id === $journalEntry->tenant_id;
    }

    public function delete(User $user, JournalEntry $journalEntry): bool
    {
        return $user->hasPermission('journal_entries.delete')
            && $user->tenant_id === $journalEntry->tenant_id;
    }

    public function post(User $user, JournalEntry $journalEntry): bool
    {
        return $user->hasPermission('journal_entries.post')
            && $user->tenant_id === $journalEntry->tenant_id;
    }

    public function reverse(User $user, JournalEntry $journalEntry): bool
    {
        return $user->hasPermission('journal_entries.reverse')
            && $user->tenant_id === $journalEntry->tenant_id;
    }

    public function restore(User $user, JournalEntry $journalEntry): bool
    {
        return $user->hasPermission('journal_entries.delete')
            && $user->tenant_id === $journalEntry->tenant_id;
    }

    public function forceDelete(User $user, JournalEntry $journalEntry): bool
    {
        return $user->hasPermission('journal_entries.force_delete')
            && $user->tenant_id === $journalEntry->tenant_id;
    }
}
