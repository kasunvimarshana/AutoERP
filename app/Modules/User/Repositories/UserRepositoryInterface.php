<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Modules\Core\Contracts\RepositoryPortInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;

interface UserRepositoryInterface extends RepositoryPortInterface
{
    public function countByTenant(int $tenantId): int;

    public function lockByIdForTenant(int|string $id, int $tenantId): ?DataRecord;

    public function findActivePlatformOperatorCredentials(string $email): ?DataRecord;

    public function findByTenantAndEmail(?int $tenantId, string $email, ?int $excludeId = null): ?DataRecord;

    public function findByTenantAndUsername(?int $tenantId, string $username, ?int $excludeId = null): ?DataRecord;

    public function findByTenantAndLoginIdentifier(int $tenantId, string $identifier): ?DataRecord;

    public function findByTenantAndIdentityReference(
        int $tenantId,
        string $providerKey,
        string $providerUserKey,
    ): ?DataRecord;

    public function pageByFilters(
        ?int $tenantId,
        ?string $search,
        ?string $status,
        ?int $roleId,
        ?int $organizationUnitId,
        int $perPage,
        int $page,
    ): PagedResult;
}
