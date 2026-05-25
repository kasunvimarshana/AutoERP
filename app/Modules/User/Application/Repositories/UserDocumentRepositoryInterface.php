<?php

declare(strict_types=1);

namespace Modules\User\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface UserDocumentRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantUserName(?int $tenantId, int $userId, string $name, ?int $excludeId = null): ?DataRecord;
}
