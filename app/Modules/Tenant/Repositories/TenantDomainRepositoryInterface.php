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

    /** Global lookup for trusted host resolution and platform workflows only. */
    public function findByDomainFromControlPlane(string $domain): ?DataRecord;

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
        int $expectedVersion,
        bool $verified,
        ?string $error,
        DateTimeInterface $attemptedAt,
        ?DateTimeInterface $revalidationDueAt = null,
        ?DateTimeInterface $graceExpiresAt = null,
    ): ?DataRecord;

    /**
     * Atomically claims due records so concurrent schedulers cannot process the same domain.
     *
     * @return list<DataRecord>
     */
    public function claimDueForRevalidation(
        DateTimeInterface $dueAt,
        DateTimeInterface $claimedAt,
        DateTimeInterface $staleBefore,
        string $claimToken,
        int $limit,
    ): array;


    public function releaseRevalidationClaim(
        int|string $id,
        int $tenantId,
        int $expectedVersion,
        string $claimToken,
        ?string $error,
        DateTimeInterface $releasedAt,
    ): ?DataRecord;

    /**
     * @return array{domain:DataRecord,fallback_primary:DataRecord|null,primary_lost:bool}|null
     */
    public function disableAfterFailedRevalidation(
        int|string $id,
        int $tenantId,
        int $expectedVersion,
        string $claimToken,
        ?string $error,
        DateTimeInterface $attemptedAt,
        ?int $updatedBy,
    ): ?array;
}
