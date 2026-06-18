<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;

final class UserAuthorizationService
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly UserAccessResolver $access,
    ) {}

    public function canCurrent(string $permission): bool
    {
        $userId = $this->currentUser->currentUserId();
        $tenantId = $this->currentTenant->currentTenantId() ?? $this->currentUser->currentTenantId();

        if ($userId === null || $tenantId === null) {
            return false;
        }

        return $this->can($userId, $tenantId, $permission);
    }

    public function can(int $userId, int $tenantId, string $permission): bool
    {
        return $this->access->can($userId, $tenantId, $permission);
    }
}
