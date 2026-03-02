<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\Contracts\ServiceContract;
use Modules\Warehouse\Application\DTOs\CreatePickingOrderDTO;
use Modules\Warehouse\Domain\Contracts\WarehouseRepositoryContract;
use Modules\Warehouse\Domain\Entities\PickingOrder;
use Modules\Warehouse\Domain\Entities\PickingOrderLine;
use Modules\Warehouse\Domain\Entities\PutawayRule;

/**
 * Warehouse service.
 *
 * Orchestrates warehouse use cases: picking order creation and putaway recommendations.
 * All mutations are wrapped in DB::transaction().
 */
class WarehouseService implements ServiceContract
{
    public function __construct(
        private readonly WarehouseRepositoryContract $repository,
    ) {}

    /**
     * Create a picking order with its lines inside a single DB transaction.
     */
    public function createPickingOrder(CreatePickingOrderDTO $dto): PickingOrder
    {
        return DB::transaction(function () use ($dto): PickingOrder {
            $pickingOrder = PickingOrder::create([
                'warehouse_id'   => $dto->warehouseId,
                'picking_type'   => $dto->pickingType,
                'status'         => 'pending',
                'reference_type' => $dto->referenceType,
                'reference_id'   => $dto->referenceId,
            ]);

            foreach ($dto->lines as $line) {
                PickingOrderLine::create([
                    'picking_order_id'   => $pickingOrder->id,
                    'product_id'         => $line['product_id'],
                    'quantity_requested' => $line['quantity_requested'],
                    'quantity_picked'    => '0.0000',
                    'status'             => 'pending',
                ]);
            }

            return $pickingOrder->load('lines');
        });
    }

    /**
     * Show a single picking order by ID.
     */
    public function showPickingOrder(int|string $id): \Illuminate\Database\Eloquent\Model
    {
        return $this->repository->findOrFail($id);
    }

    /**
     * List all picking orders.
     */
    public function listPickingOrders(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->all();
    }

    /**
     * Complete a picking order (update status to completed).
     */
    public function completePickingOrder(int|string $id): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($id): \Illuminate\Database\Eloquent\Model {
            return $this->repository->update($id, [
                'status'       => 'completed',
                'completed_at' => now(),
            ]);
        });
    }

    /**
     * Return a putaway recommendation (zone and first available bin) for a product in a warehouse.
     *
     * Returns null when no active putaway rule matches the product.
     *
     * @return array{zone_id: int, zone_name: string, zone_type: string, bin_id: int|null, bin_code: string|null}|null
     */
    public function getPutawayRecommendation(int $productId, int $warehouseId): ?array
    {
        // Find the highest-priority active rule for this product/warehouse combination.
        $rule = PutawayRule::query()
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->where(function ($query) use ($productId): void {
                $query->where('product_id', $productId)
                      ->orWhereNull('product_id');
            })
            ->orderByDesc('priority')
            ->with('zone.binLocations')
            ->first();

        if ($rule === null || $rule->zone === null) {
            return null;
        }

        $zone           = $rule->zone;
        $availableBin   = $zone->binLocations->where('is_active', true)->first();

        return [
            'zone_id'   => $zone->id,
            'zone_name' => $zone->name,
            'zone_type' => $zone->zone_type,
            'bin_id'    => $availableBin?->id,
            'bin_code'  => $availableBin?->bin_code,
        ];
    }
}
