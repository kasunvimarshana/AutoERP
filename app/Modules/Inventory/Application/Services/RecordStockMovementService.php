<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Inventory\Application\Contracts\RecordStockMovementServiceInterface;
use Modules\Inventory\Application\DTOs\RecordStockMovementDTO;
use Modules\Inventory\Domain\Entities\StockMovement;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryStockRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\TraceLogRepositoryInterface;

class RecordStockMovementService implements RecordStockMovementServiceInterface
{
    public function __construct(
        private readonly InventoryStockRepositoryInterface $inventoryStockRepository,
        private readonly TraceLogRepositoryInterface $traceLogRepository,
    ) {}

    public function execute(array $data): StockMovement
    {
        $dto = new RecordStockMovementDTO(
            tenantId: (int) $data['tenant_id'],
            warehouseId: (int) $data['warehouse_id'],
            productId: (int) $data['product_id'],
            variantId: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            batchId: isset($data['batch_id']) ? (int) $data['batch_id'] : null,
            serialId: isset($data['serial_id']) ? (int) $data['serial_id'] : null,
            fromLocationId: isset($data['from_location_id']) ? (int) $data['from_location_id'] : null,
            toLocationId: isset($data['to_location_id']) ? (int) $data['to_location_id'] : null,
            movementType: (string) $data['movement_type'],
            referenceType: $data['reference_type'] ?? null,
            referenceId: isset($data['reference_id']) ? (int) $data['reference_id'] : null,
            uomId: (int) $data['uom_id'],
            quantity: (string) $data['quantity'],
            unitCost: isset($data['unit_cost']) ? (string) $data['unit_cost'] : null,
            performedBy: isset($data['performed_by']) ? (int) $data['performed_by'] : null,
            performedAt: $data['performed_at'] ?? null,
            notes: $data['notes'] ?? null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        if (! $this->inventoryStockRepository->warehouseExists($dto->tenantId, $dto->warehouseId)) {
            throw new NotFoundException('Warehouse', $dto->warehouseId);
        }

        if ($dto->fromLocationId !== null && ! $this->inventoryStockRepository->locationBelongsToWarehouse($dto->tenantId, $dto->warehouseId, $dto->fromLocationId)) {
            throw new \InvalidArgumentException('From location does not belong to the warehouse.');
        }

        if ($dto->toLocationId !== null && ! $this->inventoryStockRepository->locationBelongsToWarehouse($dto->tenantId, $dto->warehouseId, $dto->toLocationId)) {
            throw new \InvalidArgumentException('To location does not belong to the warehouse.');
        }

        $movement = new StockMovement(
            tenantId: $dto->tenantId,
            productId: $dto->productId,
            variantId: $dto->variantId,
            batchId: $dto->batchId,
            serialId: $dto->serialId,
            fromLocationId: $dto->fromLocationId,
            toLocationId: $dto->toLocationId,
            movementType: $dto->movementType,
            referenceType: $dto->referenceType,
            referenceId: $dto->referenceId,
            uomId: $dto->uomId,
            quantity: $dto->quantity,
            unitCost: $dto->unitCost,
            performedBy: $dto->performedBy,
            performedAt: $dto->performedAt !== null ? new \DateTimeImmutable($dto->performedAt) : null,
            notes: $dto->notes,
            metadata: $dto->metadata,
        );

        $saved = $this->inventoryStockRepository->recordMovement($movement);
        $this->inventoryStockRepository->adjustStockLevel($saved);
        $this->traceLogRepository->recordForMovement($saved);

        return $saved;
    }
}
