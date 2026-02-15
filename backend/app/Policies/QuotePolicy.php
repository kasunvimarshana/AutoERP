<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Sales\Models\Quote;

class QuotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-quotes');
    }

    public function view(User $user, Quote $quote): bool
    {
        if ($user->tenant_id !== $quote->tenant_id) {
            return false;
        }

        if (!$user->can('view-quotes')) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id && $quote->organization_id) {
            if ($user->organization_id !== $quote->organization_id) {
                return false;
            }
        }

        if ($user->branch_id && $quote->branch_id) {
            return $user->branch_id === $quote->branch_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('create-quotes');
    }

    public function update(User $user, Quote $quote): bool
    {
        if ($user->tenant_id !== $quote->tenant_id) {
            return false;
        }

        if (!$user->can('edit-quotes')) {
            return false;
        }

        if (!$quote->isEditable()) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id && $quote->organization_id) {
            if ($user->organization_id !== $quote->organization_id) {
                return false;
            }
        }

        if ($user->branch_id && $quote->branch_id) {
            return $user->branch_id === $quote->branch_id;
        }

        return true;
    }

    public function delete(User $user, Quote $quote): bool
    {
        if ($user->tenant_id !== $quote->tenant_id) {
            return false;
        }

        if (!$user->can('delete-quotes')) {
            return false;
        }

        if ($quote->isConverted()) {
            return false;
        }

        return $user->hasRole('super-admin') || $user->hasRole('admin');
    }
}
