<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\CycleCount;

interface CycleCountRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?CycleCount;

    /** @return CycleCount[] */
    public function findAll(string $tenantId): array;

    /** @return CycleCount[] */
    public function findByStatus(string $tenantId, string $status): array;

    public function save(CycleCount $cycleCount): void;

    public function delete(string $tenantId, string $id): void;
}
