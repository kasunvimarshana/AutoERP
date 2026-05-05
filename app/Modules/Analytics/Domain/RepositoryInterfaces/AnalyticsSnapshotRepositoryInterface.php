<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\RepositoryInterfaces;

use Modules\Analytics\Domain\Entities\AnalyticsSnapshot;

interface AnalyticsSnapshotRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?AnalyticsSnapshot;

    public function findByTenant(int $tenantId, array $filters = []): array;

    public function save(AnalyticsSnapshot $snapshot): AnalyticsSnapshot;

    public function delete(int $id, int $tenantId): void;
}
