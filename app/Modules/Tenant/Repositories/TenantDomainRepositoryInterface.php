<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use DateTimeInterface;
use Modules\Core\DTOs\DataRecord;

interface TenantDomainRepositoryInterface
{
    /** @return list<DataRecord> */
    public function listByTenant(int $tenantId): array;

    public function findByIdForTenant(int|string $id, int $tenantId): ?DataRecord;

    public function findByDomain(string $domain): ?DataRecord;

    public function findPrimaryByTenant(int $tenantId): ?DataRecord;

    public function create(array $attributes): DataRecord;

    public function updateWithVersion(
        int|string $id,
        int $tenantId,
        int $expectedVersion,
        array $attributes,
    ): ?DataRecord;

    public function setPrimaryWithVersion(
        int|string $id,
        int $tenantId,
        int $expectedVersion,
        ?int $updatedBy,
    ): ?DataRecord;

    public function deleteWithVersion(int|string $id, int $tenantId, int $expectedVersion): bool;

    public function recordVerificationAttempt(
        int|string $id,
        int $tenantId,
        bool $verified,
        ?string $error,
        DateTimeInterface $attemptedAt,
        ?DateTimeInterface $revalidationDueAt = null,
        ?DateTimeInterface $graceExpiresAt = null,
    ): void;

    /** @return list<DataRecord> */
    public function listDueForRevalidation(DateTimeInterface $dueAt, int $limit): array;
}
