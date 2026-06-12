<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;

interface UserDocumentRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantUserName(?int $tenantId, int $userId, string $name, ?int $excludeId = null): ?DataRecord;
}
