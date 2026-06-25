<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use DateTimeInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;

interface TenantSubscriptionRepositoryInterface
{
    public function findCurrentByTenant(int $tenantId, bool $lockForUpdate = false): ?DataRecord;

    public function findByIdForTenant(int|string $id, int $tenantId, bool $lockForUpdate = false): ?DataRecord;

    public function pageHistory(int $tenantId, int $perPage, int $page): PagedResult;

    /** @param array<string, mixed> $attributes */
    public function createRevision(int $tenantId, array $attributes, ?int $actorId): DataRecord;

    public function assignCurrent(
        int $tenantId,
        int $subscriptionId,
        ?int $expectedPointerVersion,
        ?int $actorId,
        ?string $reason,
    ): ?DataRecord;

    public function transitionCurrentState(
        int $tenantId,
        int $expectedPointerVersion,
        string $state,
        ?string $reason,
        ?int $actorId,
    ): ?DataRecord;

    /** @return list<DataRecord> */
    public function listExpiredCurrent(DateTimeInterface $now, int $limit): array;

    public function recordEvent(
        int $tenantId,
        int $subscriptionId,
        ?int $previousSubscriptionId,
        string $eventType,
        ?string $reason,
        ?int $actorId,
        DateTimeInterface $occurredAt,
    ): void;
}
