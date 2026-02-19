<?php

declare(strict_types=1);

namespace Modules\CRM\Policies;

use Modules\Auth\Models\User;
use Modules\CRM\Models\Contact;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('contacts.view');
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->hasPermission('contacts.view')
            && $user->tenant_id === $contact->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('contacts.create');
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->hasPermission('contacts.update')
            && $user->tenant_id === $contact->tenant_id;
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->hasPermission('contacts.delete')
            && $user->tenant_id === $contact->tenant_id;
    }
}
