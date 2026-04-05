<?php
declare(strict_types=1);
namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\AddValuationLayerServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryValuationLayer;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;

class AddValuationLayerService implements AddValuationLayerServiceInterface
{
    public function __construct(
        private readonly InventoryValuationLayerRepositoryInterface $layerRepo,
    ) {}

    public function execute(
        int $tenantId,
        int $productId,
        int $warehouseId,
        float $quantity,
        float $unitCost,
        string $reference,
        ?int $batchId = null,
    ): InventoryValuationLayer {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Valuation layer quantity must be positive.");
        }

        return $this->layerRepo->create([
            'tenant_id'          => $tenantId,
            'product_id'         => $productId,
            'warehouse_id'       => $warehouseId,
            'quantity'           => $quantity,
            'quantity_remaining' => $quantity,
            'unit_cost'          => $unitCost,
            'received_at'        => now(),
            'reference'          => $reference,
            'batch_id'           => $batchId,
        ]);
    }
}
