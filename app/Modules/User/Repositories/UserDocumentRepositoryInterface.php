<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface UserDocumentRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantUserName(?int $tenantId, int $userId, string $name, ?int $excludeId = null): ?DataRecord;
}
