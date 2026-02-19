<?php

declare(strict_types=1);

namespace Modules\Purchase\Policies;

use Modules\Auth\Models\User;
use Modules\Purchase\Models\Vendor;

class VendorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('vendors.view');
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $user->hasPermission('vendors.view')
            && $user->tenant_id === $vendor->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('vendors.create');
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $user->hasPermission('vendors.update')
            && $user->tenant_id === $vendor->tenant_id;
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->hasPermission('vendors.delete')
            && $user->tenant_id === $vendor->tenant_id;
    }

    public function restore(User $user, Vendor $vendor): bool
    {
        return $user->hasPermission('vendors.delete')
            && $user->tenant_id === $vendor->tenant_id;
    }

    public function forceDelete(User $user, Vendor $vendor): bool
    {
        return $user->hasPermission('vendors.force_delete')
            && $user->tenant_id === $vendor->tenant_id;
    }
}
