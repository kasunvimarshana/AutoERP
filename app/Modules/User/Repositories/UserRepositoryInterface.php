<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Repositories\Contracts\RepositoryPortInterface;

interface UserRepositoryInterface extends RepositoryPortInterface
{
    public function findByTenantAndEmail(?int $tenantId, string $email, ?int $excludeId = null): ?DataRecord;

    public function findByTenantAndIdentityReference(
        int $tenantId,
        string $providerKey,
        string $providerUserKey,
    ): ?DataRecord;

    public function pageByFilters(?int $tenantId, ?string $search, int $perPage, int $page): PagedResult;
}
