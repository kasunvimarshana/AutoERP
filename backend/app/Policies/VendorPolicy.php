<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Procurement\Models\Vendor;
use Illuminate\Auth\Access\HandlesAuthorization;

class VendorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_vendors');
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $user->can('view_vendors') && $user->tenant_id === $vendor->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create_vendors');
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $user->can('update_vendors') && $user->tenant_id === $vendor->tenant_id;
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->can('delete_vendors') && $user->tenant_id === $vendor->tenant_id;
    }

    public function restore(User $user, Vendor $vendor): bool
    {
        return $user->can('restore_vendors') && $user->tenant_id === $vendor->tenant_id;
    }

    public function forceDelete(User $user, Vendor $vendor): bool
    {
        return $user->can('force_delete_vendors') && $user->tenant_id === $vendor->tenant_id;
    }
}
