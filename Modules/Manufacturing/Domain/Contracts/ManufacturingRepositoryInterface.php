<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Domain\Contracts;

use Modules\Manufacturing\Domain\Entities\Bom;
use Modules\Manufacturing\Domain\Entities\ProductionOrder;

interface ManufacturingRepositoryInterface
{
    // ── BOM ───────────────────────────────────────────────────────────────

    public function createBom(array $data): Bom;
    public function findBomById(int $id, int $tenantId): Bom;

    /** @return Bom[] */
    public function listBoms(int $tenantId, int $page, int $perPage): array;

    // ── Production Orders ─────────────────────────────────────────────────

    public function createProductionOrder(array $data): ProductionOrder;
    public function findProductionOrderById(int $id, int $tenantId): ProductionOrder;
    public function updateProductionOrderStatus(int $id, int $tenantId, string $status): ProductionOrder;
    public function updateProductionOrderCompletion(int $id, int $tenantId, string $producedQuantity): ProductionOrder;

    /** @return ProductionOrder[] */
    public function listProductionOrders(int $tenantId, int $page, int $perPage): array;
}
