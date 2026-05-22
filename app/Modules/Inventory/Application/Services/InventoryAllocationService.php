<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\DTOs\AllocationRequest;
use Modules\Inventory\Application\DTOs\AllocationResult;
use Modules\Inventory\Application\Factories\AllocationRuleFactory;
use Modules\Inventory\Application\Factories\AllocationStrategyFactory;
use Modules\Inventory\Domain\Enums\AllocationMethod;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockLevelModel;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockReservationModel;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemModel;

final class InventoryAllocationService
{
    /**
     * @param string[] $defaultRuleKeys
     */
    public function __construct(
        private readonly AllocationStrategyFactory $strategyFactory,
        private readonly AllocationRuleFactory $ruleFactory,
        private readonly array $defaultRuleKeys = [],
    ) {
    }

    public function allocate(AllocationRequest $request): AllocationResult
    {
        return DB::transaction(function () use ($request): AllocationResult {
            $method = $this->resolveAllocationMethod($request);
            $strategy = $this->strategyFactory->make($method);

            $candidates = $this->loadCandidates($request);
            $ruleKeys = !empty($request->ruleKeys) ? $request->ruleKeys : $this->defaultRuleKeys;

            foreach ($this->ruleFactory->makeMany($ruleKeys) as $rule) {
                $candidates = $rule->apply($candidates, $request);
            }

            $result = $strategy->allocate($candidates, $request);

            if ($request->persistReservation) {
                $this->persistReservations($request, $result);
            }

            return $result;
        });
    }

    private function resolveAllocationMethod(AllocationRequest $request): string
    {
        if ($request->allocationMethod !== null && $request->allocationMethod !== '') {
            return AllocationMethod::normalize($request->allocationMethod);
        }

        $item = ItemModel::query()->find($request->itemId);

        return AllocationMethod::normalize(
            $item?->allocation_method,
            (string) config('inventory.allocation.default_method', AllocationMethod::QUANTITY)
        );
    }

    private function loadCandidates(AllocationRequest $request): Collection
    {
        $query = DB::table('stock_levels as sl')
            ->selectRaw('sl.id as stock_level_id')
            ->selectRaw('sl.location_id')
            ->selectRaw('sl.batch_id')
            ->selectRaw('sl.serial_id')
            ->selectRaw('sl.unit_cost')
            ->selectRaw('b.batch_number')
            ->selectRaw('b.lot_number')
            ->selectRaw('b.expiry_date')
            ->selectRaw('(sl.quantity_on_hand - sl.quantity_reserved) as available_quantity')
            ->leftJoin('batches as b', 'b.id', '=', 'sl.batch_id')
            ->leftJoin('warehouse_locations as wl', 'wl.id', '=', 'sl.location_id')
            ->where('sl.tenant_id', $request->tenantId)
            ->where('sl.item_id', $request->itemId)
            ->whereRaw('(sl.quantity_on_hand - sl.quantity_reserved) > 0')
            ->when($request->variantId !== null, static fn ($q) => $q->where('sl.variant_id', $request->variantId))
            ->when($request->locationId !== null, static fn ($q) => $q->where('sl.location_id', $request->locationId))
            ->when(
                $request->warehouseId !== null,
                static fn ($q) => $q->where('wl.warehouse_id', $request->warehouseId)
            )
            ->orderBy('sl.id');

        return $query->lockForUpdate()->get();
    }

    private function persistReservations(AllocationRequest $request, AllocationResult $result): void
    {
        foreach ($result->lines as $line) {
            StockReservationModel::query()->create([
                'tenant_id' => $request->tenantId,
                'organization_unit_id' => $request->organizationUnitId,
                'metadata' => !empty($request->metadata) ? $request->metadata : null,
                'item_id' => $request->itemId,
                'variant_id' => $request->variantId,
                'batch_id' => $line->batchId,
                'serial_id' => $line->serialId,
                'location_id' => $line->locationId,
                'quantity' => $line->quantity,
                'reserved_for_type' => $request->referenceType,
                'reserved_for_id' => $request->referenceId,
                'expires_at' => $request->expiresAt,
                'unit_cost' => $line->unitCost,
            ]);

            StockLevelModel::query()
                ->where('id', $line->stockLevelId)
                ->lockForUpdate()
                ->increment('quantity_reserved', $line->quantity, ['row_version' => DB::raw('row_version + 1')]);
        }
    }
}
