<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface UserDeviceRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantUserDeviceToken(?int $tenantId, int $userId, string $deviceToken, ?int $excludeId = null): ?DataRecord;
}
