<?php

declare(strict_types=1);

namespace App\Modules\POS\Policies;

use App\Models\User;
use App\Modules\POS\Models\POSSession;
use App\Modules\POS\Models\POSTransaction;

class POSPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-pos');
    }

    public function view(User $user, $model): bool
    {
        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        return $user->can('view-pos');
    }

    public function create(User $user): bool
    {
        return $user->can('create-pos');
    }

    public function update(User $user, $model): bool
    {
        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        return $user->can('edit-pos');
    }

    public function delete(User $user, $model): bool
    {
        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        return $user->can('delete-pos');
    }
}
