<?php

declare(strict_types=1);

namespace Modules\User\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface UserDeviceRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantUserDeviceToken(?int $tenantId, int $userId, string $deviceToken, ?int $excludeId = null): ?DataRecord;
}
