<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\AllocateStockServiceInterface;
use Modules\Inventory\Application\Contracts\InventoryManagerServiceInterface;
use Modules\Inventory\Application\Contracts\IssueStockServiceInterface;
use Modules\Inventory\Application\Contracts\ReceiveStockServiceInterface;
use Modules\Inventory\Application\DTOs\IssueStockData;
use Modules\Inventory\Application\DTOs\ReceiveStockData;

/**
 * Unified inventory orchestrator.
 *
 * Provides a single entry-point for common inventory operations
 * (receive, issue, allocate) while delegating to the focused
 * single-responsibility services underneath.
 */
class InventoryManagerService implements InventoryManagerServiceInterface
{
    public function __construct(
        private readonly ReceiveStockServiceInterface $receiveService,
        private readonly IssueStockServiceInterface   $issueService,
        private readonly AllocateStockServiceInterface $allocateService,
    ) {}

    public function allocateStock(
        int $tenantId,
        int $productId,
        int $warehouseId,
        float $quantity,
        string $strategy = 'fefo',
    ): array {
        return $this->allocateService->execute(
            $tenantId,
            $productId,
            $warehouseId,
            $quantity,
            $strategy,
        );
    }

    public function receiveStock(
        int $tenantId,
        int $productId,
        int $warehouseId,
        float $quantity,
        float $unitCost,
        ?int $locationId = null,
        ?string $batchNumber = null,
        ?\DateTimeInterface $expiresAt = null,
    ): void {
        $dto                    = new ReceiveStockData();
        $dto->tenant_id         = $tenantId;
        $dto->product_id        = $productId;
        $dto->warehouse_id      = $warehouseId;
        $dto->quantity          = $quantity;
        $dto->unit_cost         = $unitCost;
        $dto->location_id       = $locationId;
        $dto->batch_number      = $batchNumber;
        $dto->expires_at        = $expiresAt?->format('Y-m-d');

        $this->receiveService->execute($dto);
    }

    public function issueStock(
        int $tenantId,
        int $productId,
        int $warehouseId,
        float $quantity,
        ?int $locationId = null,
        string $strategy = 'fefo',
    ): void {
        $dto                      = new IssueStockData();
        $dto->tenant_id           = $tenantId;
        $dto->product_id          = $productId;
        $dto->warehouse_id        = $warehouseId;
        $dto->quantity            = $quantity;
        $dto->location_id         = $locationId;
        $dto->allocation_strategy = $strategy;

        $this->issueService->execute($dto);
    }
}
