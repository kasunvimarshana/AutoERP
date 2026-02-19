<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use Modules\Auth\Models\User;
use Modules\Sales\Models\Quotation;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('quotations.view');
    }

    public function view(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.view')
            && $user->tenant_id === $quotation->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('quotations.create');
    }

    public function update(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.update')
            && $user->tenant_id === $quotation->tenant_id;
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.delete')
            && $user->tenant_id === $quotation->tenant_id;
    }

    public function restore(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.delete')
            && $user->tenant_id === $quotation->tenant_id;
    }

    public function forceDelete(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.force_delete')
            && $user->tenant_id === $quotation->tenant_id;
    }
}
