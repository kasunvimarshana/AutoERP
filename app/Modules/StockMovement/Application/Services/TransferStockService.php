<?php
declare(strict_types=1);
namespace Modules\StockMovement\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;
use Modules\StockMovement\Application\Contracts\TransferStockServiceInterface;
use Modules\StockMovement\Domain\Entities\StockMovement;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;

class TransferStockService implements TransferStockServiceInterface
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $movementRepo,
        private readonly InventoryLevelRepositoryInterface $levelRepo,
    ) {}

    public function execute(
        int $tenantId,
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $quantity,
        float $unitCost,
        string $reference,
        int $createdBy,
        ?int $fromLocationId = null,
        ?int $toLocationId = null,
        ?string $notes = null,
    ): array {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Transfer quantity must be positive.");
        }
        if ($fromWarehouseId === $toWarehouseId) {
            throw new \InvalidArgumentException("Source and destination warehouses must differ.");
        }

        return DB::transaction(function () use (
            $tenantId, $productId, $fromWarehouseId, $toWarehouseId,
            $quantity, $unitCost, $reference, $createdBy, $fromLocationId, $toLocationId, $notes
        ): array {
            // 1. Validate source has sufficient stock
            $sourceLevel = $this->levelRepo->findByProduct($tenantId, $productId, $fromWarehouseId);
            if (!$sourceLevel || $sourceLevel->getAvailableQuantity() < $quantity - \Modules\Inventory\Domain\Entities\InventoryLevel::FLOAT_TOLERANCE) {
                $available = $sourceLevel ? $sourceLevel->getAvailableQuantity() : 0.0;
                throw new \DomainException(
                    "Insufficient stock in source warehouse [{$fromWarehouseId}]. Available: {$available}, Requested: {$quantity}."
                );
            }

            // 2. Deduct from source
            $sourceLevel->issue($quantity);
            $this->levelRepo->update($sourceLevel->getId(), [
                'quantity_on_hand' => $sourceLevel->getQuantityOnHand(),
            ]);

            // 3. Add to destination
            $destLevel = $this->levelRepo->upsert(
                $tenantId, $productId, $toWarehouseId, $toLocationId,
                $sourceLevel->getValuationMethod()
            );
            $destLevel->receive($quantity);
            $this->levelRepo->update($destLevel->getId(), [
                'quantity_on_hand' => $destLevel->getQuantityOnHand(),
            ]);

            $movedAt = now();

            // 4. Record ISSUE movement (outbound from source)
            $issue = $this->movementRepo->create([
                'tenant_id'        => $tenantId,
                'product_id'       => $productId,
                'warehouse_id'     => $fromWarehouseId,
                'from_location_id' => $fromLocationId,
                'to_location_id'   => null,
                'movement_type'    => StockMovement::TYPE_TRANSFER,
                'quantity'         => -$quantity,
                'unit_cost'        => $unitCost,
                'reference'        => $reference,
                'notes'            => $notes,
                'created_by'       => $createdBy,
                'moved_at'         => $movedAt,
            ]);

            // 5. Record RECEIPT movement (inbound to destination)
            $receipt = $this->movementRepo->create([
                'tenant_id'        => $tenantId,
                'product_id'       => $productId,
                'warehouse_id'     => $toWarehouseId,
                'from_location_id' => null,
                'to_location_id'   => $toLocationId,
                'movement_type'    => StockMovement::TYPE_TRANSFER,
                'quantity'         => $quantity,
                'unit_cost'        => $unitCost,
                'reference'        => $reference,
                'notes'            => $notes,
                'created_by'       => $createdBy,
                'moved_at'         => $movedAt,
            ]);

            return [$issue, $receipt];
        });
    }
}
