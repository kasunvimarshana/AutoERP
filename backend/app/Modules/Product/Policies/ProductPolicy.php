<?php

namespace App\Modules\Product\Policies;

use App\Models\User;
use App\Modules\Product\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('products.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('products.delete');
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('products.restore');
    }
}
