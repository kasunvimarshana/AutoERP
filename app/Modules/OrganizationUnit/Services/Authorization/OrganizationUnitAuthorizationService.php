<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\Authorization;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\Core\Contracts\PermissionCheckerInterface;

final class OrganizationUnitAuthorizationService
{
    public function __construct(private readonly PermissionCheckerInterface $permissions) {}

    public function assert(?int $userId, int $tenantId, string $permission): void
    {
        if ($userId === null || ! $this->permissions->allows($userId, $tenantId, $permission)) {
            throw new AuthorizationException('This organization-unit action requires permission: '.$permission);
        }
    }
}
