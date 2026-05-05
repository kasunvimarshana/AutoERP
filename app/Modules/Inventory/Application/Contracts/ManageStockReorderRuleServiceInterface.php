<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Domain\Entities\StockReorderRule;

interface ManageStockReorderRuleServiceInterface
{
    public function create(array $data): StockReorderRule;

    public function update(int $tenantId, int $id, array $data): StockReorderRule;

    public function delete(int $tenantId, int $id): void;

    public function find(int $tenantId, int $id): StockReorderRule;

    public function list(int $tenantId, int $perPage = 15, int $page = 1): mixed;

    public function listLowStock(int $tenantId): array;
}
