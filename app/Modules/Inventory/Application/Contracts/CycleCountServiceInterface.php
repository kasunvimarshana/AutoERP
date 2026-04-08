<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\CycleCount;
use Modules\Inventory\Domain\Entities\CycleCountLine;

interface CycleCountServiceInterface
{
    public function getCycleCount(string $tenantId, string $id): CycleCount;

    /** @return CycleCount[] */
    public function getAllCycleCounts(string $tenantId): array;

    public function createCycleCount(string $tenantId, array $data): CycleCount;

    public function startCycleCount(string $tenantId, string $id): CycleCount;

    public function completeCycleCount(string $tenantId, string $id): CycleCount;

    public function cancelCycleCount(string $tenantId, string $id): CycleCount;

    public function updateCycleCount(string $tenantId, string $id, array $data): CycleCount;

    public function deleteCycleCount(string $tenantId, string $id): void;

    public function addCycleCountLine(string $tenantId, string $cycleCountId, array $data): CycleCountLine;

    public function updateCycleCountLine(string $tenantId, string $lineId, float $countedQty): CycleCountLine;
}
