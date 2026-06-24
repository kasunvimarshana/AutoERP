<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use DateTimeInterface;
use Modules\Core\DTOs\DataRecord;

interface TenantSubscriptionRepositoryInterface
{
    public function findCurrentByTenant(int $tenantId): ?DataRecord;

    public function findById(int|string $id): ?DataRecord;

    /** @param array<string, mixed> $attributes */
    public function replaceCurrent(int $tenantId, array $attributes, ?int $actorId): DataRecord;

    /** @return list<DataRecord> */
    public function listExpiredCurrent(DateTimeInterface $now, int $limit): array;

    public function expireWithVersion(int|string $id, int $expectedVersion): bool;
}
