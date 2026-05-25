<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Presentation\Policies;

use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

final class OrganizationUnitPolicy
{
    public function viewAny(UserModel $user): bool
    {
        return true;
    }

    public function view(UserModel $user, OrganizationUnitModel $organizationUnit): bool
    {
        return true;
    }

    public function create(UserModel $user): bool
    {
        return true;
    }

    public function update(UserModel $user, OrganizationUnitModel $organizationUnit): bool
    {
        return true;
    }

    public function delete(UserModel $user, OrganizationUnitModel $organizationUnit): bool
    {
        return true;
    }
}