<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Contracts;

use Modules\Analytics\Application\DTOs\CreateAnalyticsSnapshotDTO;
use Modules\Analytics\Domain\Entities\AnalyticsSnapshot;

interface AnalyticsServiceInterface
{
    public function getSnapshotById(int $id, int $tenantId): AnalyticsSnapshot;

    public function listSnapshots(int $tenantId, array $filters = []): array;

    public function createSnapshot(CreateAnalyticsSnapshotDTO $dto): AnalyticsSnapshot;

    public function getSummary(int $tenantId, ?int $orgUnitId = null): array;

    public function deleteSnapshot(int $id, int $tenantId): void;
}
