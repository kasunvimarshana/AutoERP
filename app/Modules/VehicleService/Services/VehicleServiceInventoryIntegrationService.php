<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Services\InventoryAvailabilityService;
use Modules\Inventory\Services\InventoryFacade;
use Modules\Inventory\Services\InventoryUomService;
use Modules\Item\Enums\TrackingType;
use Modules\VehicleService\Enums\VehicleServiceLineStatus;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\VehicleService\Services\Concerns\AssertsVehicleServiceExpectedVersion;

final class VehicleServiceInventoryIntegrationService
{
    use AssertsVehicleServiceExpectedVersion;

    public function __construct(
        private readonly DecimalMath $math,
        private readonly VehicleServiceValidationService $validator,
        private readonly VehicleServiceLineService $lines,
        private readonly InventoryAvailabilityService $availability,
        private readonly InventoryFacade $inventory,
        private readonly InventoryUomService $uoms,
        private readonly VehicleServiceInventoryFinanceService $finance,
    ) {}

    /** @return Collection<int, VehicleServiceJobLine> */
    public function issueLines(
        VehicleServiceJob $job,
        ?int $warehouseId = null,
        ?int $warehouseLocationId = null,
    ): Collection {
        return $job->lines()
            ->with(['item', 'variant', 'uom'])
            ->whereNull('inventory_movement_id')
            ->get()
            ->filter(fn ($line): bool => $this->lines->isInventoryIssueLine($line))
            ->each(function ($line) use ($job, $warehouseId, $warehouseLocationId): void {
                $line->setAttribute('issue_eligible', false);
                $line->setAttribute('inventory_warning', $warehouseId === null
                    ? 'Select a warehouse to check stock availability.'
                    : ($warehouseLocationId === null ? 'Select a warehouse location to check exact stock availability.' : null));

                if ($line->item?->tracking_type !== TrackingType::None) {
                    $line->setAttribute('inventory_warning', 'Batch, lot, and serial tracked items require tracking references in the Inventory workflow.');

                    return;
                }
                if ($warehouseId === null || $warehouseLocationId === null) {
                    return;
                }

                $stock = $this->availability->availability(new StockBalanceData(
                    tenantId: (int) $job->tenant_id,
                    itemId: (int) $line->item_id,
                    warehouseId: $warehouseId,
                    organizationUnitId: $job->organization_unit_id,
                    itemVariantId: $line->item_variant_id,
                    warehouseLocationId: $warehouseLocationId,
                ));
                $required = $this->uoms->quantity(
                    (int) $job->tenant_id,
                    $job->organization_unit_id,
                    $line->item,
                    $line->uom_id,
                    (string) $line->quantity,
                );
                $eligible = $this->math->compare($stock->quantityAvailable, $required) >= 0;
                $line->setAttribute('stock_on_hand', $stock->quantityOnHand);
                $line->setAttribute('stock_available', $stock->quantityAvailable);
                $line->setAttribute('issue_eligible', $eligible);
                $line->setAttribute('inventory_warning', $eligible ? null : 'Available stock is below the required quantity.');
            })
            ->values();
    }

    /**
     * @param  list<int>  $lineIds
     * @return list<InventoryMovement>
     */
    public function issue(
        VehicleServiceJob $job,
        int $warehouseId,
        int $warehouseLocationId,
        array $lineIds = [],
        ?int $postedBy = null,
        ?int $expectedVersion = null,
    ): array {
        return DB::transaction(function () use ($job, $warehouseId, $warehouseLocationId, $lineIds, $postedBy, $expectedVersion): array {
            $job = VehicleServiceJob::query()->lockForUpdate()->findOrFail($job->getKey());
            $this->assertExpectedVersion($job, $expectedVersion);
            $this->validator->assertMutable($job);

            $query = $job->lines()->with('item')->whereNull('inventory_movement_id');
            if ($lineIds !== []) {
                $lineIds = array_values(array_unique($lineIds));
                $query->whereIn('id', $lineIds);
            }

            $issued = [];
            $selectedLines = $query->get();
            if ($lineIds !== [] && $selectedLines->count() !== count($lineIds)) {
                throw new InvalidArgumentException('One or more selected inventory lines are invalid or already issued.');
            }
            foreach ($selectedLines as $line) {
                if (! $this->lines->isInventoryIssueLine($line)) {
                    if (in_array((int) $line->getKey(), $lineIds, true)) {
                        $this->validator->assertInventoryIssueLine($line);
                    }

                    continue;
                }
                $this->validator->assertInventoryIssueLine($line);
                $movement = $this->inventory->issue(new StockMovementData(
                    tenantId: (int) $job->tenant_id,
                    movementDate: now()->toDateString(),
                    movementType: InventoryMovementType::Issue,
                    direction: InventoryDirection::Out,
                    itemId: (int) $line->item_id,
                    warehouseId: $warehouseId,
                    quantity: (string) $line->quantity,
                    organizationUnitId: $job->organization_unit_id,
                    itemVariantId: $line->item_variant_id,
                    warehouseLocationId: $warehouseLocationId,
                    unitCost: (string) $line->unit_cost,
                    sourceType: 'vehicle_service_job',
                    sourceId: (int) $job->getKey(),
                    sourceLineType: 'vehicle_service_job_line',
                    sourceLineId: (int) $line->getKey(),
                    description: 'Vehicle service job '.$job->job_number,
                    createdBy: $postedBy,
                    uomId: $line->uom_id,
                ), $postedBy);

                $this->finance->postIssue($job, $line, $movement, $postedBy);

                $line->inventory_movement_id = $movement->getKey();
                $line->status = VehicleServiceLineStatus::Issued;
                $line->save();
                $issued[] = $movement;
            }

            if ($issued !== []) {
                $this->bumpJobVersion($job);
            }

            return $issued;
        });
    }
}
