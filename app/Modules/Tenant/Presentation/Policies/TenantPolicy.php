<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Policies;

use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

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
