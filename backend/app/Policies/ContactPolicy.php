<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\CRM\Models\Contact;

/**
 * Contact authorization policy
 * 
 * Enforces fine-grained RBAC/ABAC with strict tenant isolation.
 */
class ContactPolicy
{
    /**
     * Determine whether the user can view any contacts.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-contacts');
    }

    /**
     * Determine whether the user can view the contact.
     */
    public function view(User $user, Contact $contact): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $contact->tenant_id) {
            return false;
        }

        if (!$user->can('view-contacts')) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Check customer access
        $customer = $contact->customer;
        if ($customer) {
            if ($user->organization_id && $customer->organization_id) {
                if ($user->organization_id !== $customer->organization_id) {
                    return false;
                }
            }

            if ($user->branch_id && $customer->branch_id) {
                return $user->branch_id === $customer->branch_id;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can create contacts.
     */
    public function create(User $user): bool
    {
        return $user->can('create-contacts');
    }

    /**
     * Determine whether the user can update the contact.
     */
    public function update(User $user, Contact $contact): bool
    {
        if ($user->tenant_id !== $contact->tenant_id) {
            return false;
        }

        if (!$user->can('edit-contacts')) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        $customer = $contact->customer;
        if ($customer) {
            if ($user->organization_id && $customer->organization_id) {
                if ($user->organization_id !== $customer->organization_id) {
                    return false;
                }
            }

            if ($user->branch_id && $customer->branch_id) {
                return $user->branch_id === $customer->branch_id;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can delete the contact.
     */
    public function delete(User $user, Contact $contact): bool
    {
        if ($user->tenant_id !== $contact->tenant_id) {
            return false;
        }

        if (!$user->can('delete-contacts')) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        $customer = $contact->customer;
        if ($customer) {
            if ($user->organization_id && $customer->organization_id) {
                if ($user->organization_id !== $customer->organization_id) {
                    return false;
                }
            }

            if ($user->branch_id && $customer->branch_id) {
                return $user->branch_id === $customer->branch_id;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can restore the contact.
     */
    public function restore(User $user, Contact $contact): bool
    {
        return $this->update($user, $contact);
    }

    /**
     * Determine whether the user can permanently delete the contact.
     */
    public function forceDelete(User $user, Contact $contact): bool
    {
        if ($user->tenant_id !== $contact->tenant_id) {
            return false;
        }

        if (!$user->hasAnyRole(['super-admin', 'admin'])) {
            return false;
        }

        return $user->can('delete-contacts');
    }
}
