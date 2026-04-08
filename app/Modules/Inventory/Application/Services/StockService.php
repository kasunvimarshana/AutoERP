<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Inventory\Application\Contracts\StockServiceInterface;
use Modules\Inventory\Application\DTOs\StockAdjustmentData;
use Modules\Inventory\Application\DTOs\StockTransferData;
use Modules\Inventory\Domain\Events\StockLevelUpdated;
use Modules\Inventory\Domain\Events\StockMovementCreated;
use Modules\Inventory\Domain\Exceptions\InsufficientStockException;
use Modules\Inventory\Domain\RepositoryInterfaces\StockItemRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;
use Modules\Inventory\Domain\ValueObjects\MovementType;

final class StockService implements StockServiceInterface
{
    public function __construct(
        private readonly StockItemRepositoryInterface $stockItemRepository,
        private readonly StockMovementRepositoryInterface $movementRepository,
    ) {}

    public function adjust(StockAdjustmentData $dto, int $tenantId): mixed
    {
        $dto->validate($dto->toArray());

        return DB::transaction(function () use ($dto, $tenantId) {
            $stockItem = $this->stockItemRepository->findByProductAndLocation(
                $dto->product_id,
                $dto->location_id,
                $dto->variant_id
            );

            $isOutbound = in_array($dto->movement_type, [
                MovementType::ISSUE,
                MovementType::RETURN_OUT,
                MovementType::SCRAP,
                MovementType::TRANSFER,
            ], true);

            if (! $stockItem) {
                if ($isOutbound && $dto->quantity > 0) {
                    throw new InsufficientStockException($dto->product_id, $dto->quantity, 0.0);
                }

                $stockItem = $this->stockItemRepository->create([
                    'uuid'               => (string) Str::uuid(),
                    'tenant_id'          => $tenantId,
                    'product_id'         => $dto->product_id,
                    'variant_id'         => $dto->variant_id,
                    'location_id'        => $dto->location_id,
                    'batch_lot_id'       => $dto->batch_lot_id,
                    'quantity_on_hand'   => 0.0,
                    'quantity_reserved'  => 0.0,
                    'quantity_available' => 0.0,
                    'unit_cost'          => $dto->unit_cost ?? 0.0,
                    'status'             => 'available',
                ]);
            }

            if ($isOutbound && $dto->quantity > $stockItem->quantity_available) {
                throw new InsufficientStockException(
                    $dto->product_id,
                    $dto->quantity,
                    (float) $stockItem->quantity_available
                );
            }

            $qtyDelta = $isOutbound ? -abs($dto->quantity) : abs($dto->quantity);
            $this->stockItemRepository->incrementQuantity($stockItem->id, $qtyDelta);

            $movement = $this->movementRepository->create([
                'uuid'             => (string) Str::uuid(),
                'tenant_id'        => $tenantId,
                'product_id'       => $dto->product_id,
                'variant_id'       => $dto->variant_id,
                'location_id'      => $dto->location_id,
                'batch_lot_id'     => $dto->batch_lot_id,
                'serial_number_id' => $dto->serial_number_id,
                'movement_type'    => $dto->movement_type,
                'quantity'         => $dto->quantity,
                'unit_cost'        => $dto->unit_cost,
                'reference'        => $dto->reference,
                'notes'            => $dto->notes,
            ]);

            $updatedItem = $this->stockItemRepository->find($stockItem->id);

            StockMovementCreated::dispatch($movement, $tenantId);
            StockLevelUpdated::dispatch($updatedItem, $tenantId);

            return $movement;
        });
    }

    public function transfer(StockTransferData $dto, int $tenantId): mixed
    {
        $dto->validate($dto->toArray());

        return DB::transaction(function () use ($dto, $tenantId) {
            $outDto = new StockAdjustmentData();
            $outDto->fill([
                'product_id'    => $dto->product_id,
                'location_id'   => $dto->from_location_id,
                'variant_id'    => $dto->variant_id,
                'batch_lot_id'  => $dto->batch_lot_id,
                'quantity'      => $dto->quantity,
                'movement_type' => MovementType::TRANSFER,
                'reference'     => $dto->reference,
                'notes'         => $dto->notes,
            ]);
            $this->adjust($outDto, $tenantId);

            $inDto = new StockAdjustmentData();
            $inDto->fill([
                'product_id'    => $dto->product_id,
                'location_id'   => $dto->to_location_id,
                'variant_id'    => $dto->variant_id,
                'batch_lot_id'  => $dto->batch_lot_id,
                'quantity'      => $dto->quantity,
                'movement_type' => MovementType::RECEIPT,
                'reference'     => $dto->reference,
                'notes'         => $dto->notes,
            ]);

            return $this->adjust($inDto, $tenantId);
        });
    }

    public function getStockByProduct(int $productId, int $tenantId): Collection
    {
        return $this->stockItemRepository->findByProduct($productId, $tenantId);
    }

    public function getStockByLocation(int $locationId): Collection
    {
        return $this->stockItemRepository->findByLocation($locationId);
    }

    public function listMovements(array $filters = [], ?int $perPage = null): mixed
    {
        $perPage = $perPage ?? (int) config('core.pagination.per_page', 15);
        $repo    = clone $this->movementRepository;

        foreach ($filters as $column => $value) {
            $repo->where($column, $value);
        }

        return $repo->paginate($perPage);
    }
}
