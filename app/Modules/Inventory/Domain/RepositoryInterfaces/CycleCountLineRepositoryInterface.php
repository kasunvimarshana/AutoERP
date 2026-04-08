<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\CycleCountLine;

interface CycleCountLineRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?CycleCountLine;

    /** @return CycleCountLine[] */
    public function findByCycleCount(string $tenantId, string $cycleCountId): array;

    public function save(CycleCountLine $line): void;

    public function delete(string $tenantId, string $id): void;
}
