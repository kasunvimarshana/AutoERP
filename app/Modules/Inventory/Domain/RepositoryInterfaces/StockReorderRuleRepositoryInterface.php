<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\StockReorderRule;

interface StockReorderRuleRepositoryInterface
{
    public function create(StockReorderRule $rule): StockReorderRule;

    public function update(StockReorderRule $rule): StockReorderRule;

    public function delete(int $tenantId, int $id): void;

    public function findById(int $tenantId, int $id): StockReorderRule;

    public function listByTenant(int $tenantId, int $perPage = 15, int $page = 1): mixed;

    public function listBelowMinimum(int $tenantId): array;
}
