<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use DateTimeInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;

interface TenantDomainRepositoryInterface
{
    public function pageByTenant(
        int $tenantId,
        ?string $search,
        ?string $status,
        ?string $ownershipStatus,
        ?string $operationalStatus,
        int $perPage,
        int $page,
    ): PagedResult;

    public function findByIdForTenant(int|string $id, int $tenantId): ?DataRecord;

    /** Global lookup for trusted host resolution and platform workflows only. */
    public function findByDomainFromControlPlane(string $domain): ?DataRecord;

    public function findPrimaryByTenant(int $tenantId, bool $lockForUpdate = false): ?DataRecord;

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
        ?string $errorCode,
        ?string $errorMessage,
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


    /** @return list<DataRecord> */
    public function claimDueForOperationalVerification(
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
        ?string $errorCode,
        ?string $errorMessage,
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
        ?string $errorCode,
        ?string $errorMessage,
        DateTimeInterface $attemptedAt,
        ?int $updatedBy,
    ): ?array;
}
