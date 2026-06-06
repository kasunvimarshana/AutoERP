<?php

declare(strict_types=1);

namespace Modules\Tenant\Policies;

use Modules\Tenant\Models\TenantModel;
use Modules\User\Models\UserModel;

final class TenantPolicy
{
    public function viewAny(UserModel $user): bool
    {
        return true;
    }

    public function view(UserModel $user, TenantModel $tenant): bool
    {
        return true;
    }

    public function create(UserModel $user): bool
    {
        return true;
    }

    public function update(UserModel $user, TenantModel $tenant): bool
    {
        return true;
    }

    public function activate(UserModel $user, TenantModel $tenant): bool
    {
        return true;
    }

    public function suspend(UserModel $user, TenantModel $tenant): bool
    {
        return true;
    }

    public function deactivate(UserModel $user, TenantModel $tenant): bool
    {
        return true;
    }
}
