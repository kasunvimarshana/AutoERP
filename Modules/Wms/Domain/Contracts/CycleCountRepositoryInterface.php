<?php

declare(strict_types=1);

namespace Modules\Wms\Domain\Contracts;

use Modules\Wms\Domain\Entities\CycleCount;

interface CycleCountRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?CycleCount;

    public function findAll(int $tenantId, int $warehouseId, int $page, int $perPage): array;

    public function save(CycleCount $cycleCount): CycleCount;

    public function saveLines(int $cycleCountId, int $tenantId, array $lines): array;

    public function findLines(int $cycleCountId, int $tenantId): array;

    public function delete(int $id, int $tenantId): void;
}
