<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Services\StockMovementService;
use Modules\VehicleService\Enums\VehicleServiceLineStatus;
use Modules\VehicleService\Models\VehicleServiceJob;

final class VehicleServiceInventoryIntegrationService
{
    public function __construct(
        private readonly VehicleServiceValidationService $validator,
        private readonly VehicleServiceLineService $lines,
        private readonly StockMovementService $movements,
    ) {}

    /**
     * @param  list<int>  $lineIds
     * @return list<InventoryMovement>
     */
    public function issue(
        VehicleServiceJob $job,
        int $warehouseId,
        ?int $warehouseLocationId = null,
        array $lineIds = [],
        ?int $postedBy = null,
    ): array {
        $this->validator->assertMutable($job);

        return DB::transaction(function () use ($job, $warehouseId, $warehouseLocationId, $lineIds, $postedBy): array {
            $query = $job->lines()->with('item')->whereNull('inventory_movement_id');
            if ($lineIds !== []) {
                $query->whereIn('id', $lineIds);
            }

            $issued = [];
            foreach ($query->get() as $line) {
                if (! $this->lines->isInventoryIssueLine($line)) {
                    if (in_array((int) $line->getKey(), $lineIds, true)) {
                        $this->validator->assertInventoryIssueLine($line);
                    }

                    continue;
                }
                $this->validator->assertInventoryIssueLine($line);
                $movement = $this->movements->record(new StockMovementData(
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
                ), $postedBy);

                $line->inventory_movement_id = $movement->getKey();
                $line->status = VehicleServiceLineStatus::Issued;
                $line->save();
                $issued[] = $movement;
            }

            return $issued;
        });
    }
}
