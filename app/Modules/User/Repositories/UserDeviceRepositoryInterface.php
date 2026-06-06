<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface UserDeviceRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantUserDeviceToken(?int $tenantId, int $userId, string $deviceToken, ?int $excludeId = null): ?DataRecord;
}
