<?php

namespace App\Modules\CRM\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\CRM\Models\Contact;

/**
 * Contact Policy
 * 
 * Authorization policies for contact management operations
 * 
 * @package App\Modules\CRM\Policies
 */
class ContactPolicy
{
    /**
     * Determine whether the user can view any contacts
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('contacts.view');
    }

    /**
     * Determine whether the user can view the contact
     *
     * @param User $user
     * @param Contact $contact
     * @return bool
     */
    public function view(User $user, Contact $contact): bool
    {
        return $user->can('contacts.view') && $user->tenant_id === $contact->tenant_id;
    }

    /**
     * Determine whether the user can create contacts
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('contacts.create');
    }

    /**
     * Determine whether the user can update the contact
     *
     * @param User $user
     * @param Contact $contact
     * @return bool
     */
    public function update(User $user, Contact $contact): bool
    {
        return $user->can('contacts.update') && $user->tenant_id === $contact->tenant_id;
    }

    /**
     * Determine whether the user can delete the contact
     *
     * @param User $user
     * @param Contact $contact
     * @return bool
     */
    public function delete(User $user, Contact $contact): bool
    {
        return $user->can('contacts.delete') && $user->tenant_id === $contact->tenant_id;
    }

    /**
     * Determine whether the user can restore the contact
     *
     * @param User $user
     * @param Contact $contact
     * @return bool
     */
    public function restore(User $user, Contact $contact): bool
    {
        return $user->can('contacts.delete') && $user->tenant_id === $contact->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the contact
     *
     * @param User $user
     * @param Contact $contact
     * @return bool
     */
    public function forceDelete(User $user, Contact $contact): bool
    {
        return $user->can('contacts.delete') && $user->tenant_id === $contact->tenant_id;
    }
}
