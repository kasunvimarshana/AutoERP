<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Modules\Core\Services\DecimalMath;
use Modules\Inventory\DTOs\ReservationData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Enums\ReservationStatus;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventoryReservation;
use Modules\Inventory\Services\InventoryAvailabilityService;
use Modules\Inventory\Services\InventoryFacade;
use Modules\Inventory\Services\InventoryUomService;
use Modules\VehicleService\Constants\VehicleServiceFinanceSource;
use Modules\VehicleService\Enums\VehicleServiceLineStatus;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;
use Modules\Warehouse\Services\WarehouseDefaultResolver;
use Modules\VehicleService\Services\Concerns\AssertsVehicleServiceExpectedVersion;

final class VehicleServiceInventoryIntegrationService
{
    use AssertsVehicleServiceExpectedVersion;

    public function __construct(
        private readonly DecimalMath $math,
        private readonly VehicleServiceValidationService $validator,
        private readonly VehicleServiceLineRuleService $lineRules,
        private readonly InventoryAvailabilityService $availability,
        private readonly InventoryFacade $inventory,
        private readonly InventoryUomService $uoms,
        private readonly VehicleServiceInventoryFinanceService $finance,
        private readonly WarehouseDefaultResolver $warehouseDefaults,
    ) {}

    /** @return Collection<int, VehicleServiceJobLine> */
    public function issueLines(
        VehicleServiceJob $job,
        ?int $warehouseId = null,
        ?int $warehouseLocationId = null,
    ): Collection {
        $lines = $job->lines()
            ->with(['item', 'variant', 'uom'])
            ->whereNull('inventory_movement_id')
            ->get()
            ->filter(fn ($line): bool => $this->lineRules->isInventoryIssueLine($line))
            ->each(function ($line) use ($job, $warehouseId, $warehouseLocationId): void {
                $line->setAttribute('issue_eligible', false);
                $line->setAttribute('inventory_warning', $warehouseId === null
                    ? 'Select a warehouse to check stock availability.'
                    : ($warehouseLocationId === null ? 'Select a warehouse location to check exact stock availability.' : null));

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
                    batchId: $line->batch_id,
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

        return $lines;
    }

    public function reserveLineTree(
        VehicleServiceJob $job,
        VehicleServiceJobLine $line,
        ?int $actorId = null,
    ): void {
        $this->assertInsideJobTransaction();
        $line->loadMissing(['item', 'children.item']);

        foreach ([$line, ...$line->children->all()] as $candidate) {
            if (! $this->lineRules->isInventoryIssueLine($candidate)) {
                continue;
            }
            if ($this->activeReservation($job, $candidate) instanceof InventoryReservation) {
                throw new InvalidArgumentException('This job line already has an active stock reservation.');
            }

            [$warehouse, $location] = $this->automaticSource($job);
            if (! $warehouse instanceof WarehouseModel || ! $location instanceof WarehouseLocationModel) {
                throw new InvalidArgumentException(
                    'Configure a default warehouse and location before adding stock items to a service job.',
                );
            }

            $this->inventory->reserve(new ReservationData(
                tenantId: (int) $job->tenant_id,
                reservationDate: now()->toDateString(),
                itemId: (int) $candidate->item_id,
                warehouseId: (int) $warehouse->getKey(),
                quantityReserved: (string) $candidate->quantity,
                organizationUnitId: $job->organization_unit_id,
                itemVariantId: $candidate->item_variant_id,
                warehouseLocationId: (int) $location->getKey(),
                batchId: $candidate->batch_id,
                sourceType: VehicleServiceFinanceSource::JOB,
                sourceId: (int) $job->getKey(),
                sourceLineType: VehicleServiceFinanceSource::JOB_LINE,
                sourceLineId: (int) $candidate->getKey(),
                notes: 'Reserved for vehicle service job '.$job->job_number,
                uomId: $candidate->uom_id,
                createdBy: $actorId,
            ));
        }
    }

    public function releaseLineTree(
        VehicleServiceJob $job,
        VehicleServiceJobLine $line,
        ?int $actorId = null,
    ): void {
        $this->assertInsideJobTransaction();
        $line->loadMissing('children');
        foreach ([$line, ...$line->children->all()] as $candidate) {
            $this->releaseReservation($job, $candidate, $actorId);
        }
    }

    public function releaseJobReservations(VehicleServiceJob $job, ?int $actorId = null): void
    {
        $this->assertInsideJobTransaction();
        $reservations = InventoryReservation::query()
            ->where('tenant_id', $job->tenant_id)
            ->where('source_type', VehicleServiceFinanceSource::JOB)
            ->where('source_id', $job->getKey())
            ->where('source_line_type', VehicleServiceFinanceSource::JOB_LINE)
            ->whereIn('status', [ReservationStatus::Active->value, ReservationStatus::PartiallyAllocated->value])
            ->orderBy('id')
            ->get();

        foreach ($reservations as $reservation) {
            $this->inventory->unreserve($reservation, null, $actorId);
        }
    }

    /** @return list<InventoryMovement> */
    public function issueReservedOnStart(VehicleServiceJob $job, ?int $actorId = null): array
    {
        $this->assertInsideJobTransaction();
        $lines = $job->lines()
            ->with(['item', 'batch'])
            ->whereNull('inventory_movement_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->filter(fn (VehicleServiceJobLine $line): bool => $this->lineRules->isInventoryIssueLine($line));
        $issued = [];

        foreach ($lines as $line) {
            $reservation = $this->activeReservation($job, $line);
            if (! $reservation instanceof InventoryReservation) {
                throw new InvalidArgumentException(
                    'Stock reservation is missing for '.$line->description.'. Save the line again before starting the job.',
                );
            }
            if ($reservation->warehouse_location_id === null) {
                throw new InvalidArgumentException('The stock reservation for '.$line->description.' has no issue location.');
            }

            $this->inventory->unreserve($reservation, null, $actorId);
            $issued[] = $this->postIssue(
                $job,
                $line,
                (int) $reservation->warehouse_id,
                (int) $reservation->warehouse_location_id,
                $actorId,
            );
        }

        return $issued;
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

            $query = $job->lines()->with(['item', 'batch'])->whereNull('inventory_movement_id');
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
                if (! $this->lineRules->isInventoryIssueLine($line)) {
                    if (in_array((int) $line->getKey(), $lineIds, true)) {
                        $this->validator->assertInventoryIssueLine($line);
                    }

                    continue;
                }
                $this->validator->assertInventoryIssueLine($line);
                $this->releaseReservation($job, $line, $postedBy);
                $movement = $this->postIssue($job, $line, $warehouseId, $warehouseLocationId, $postedBy);
                $issued[] = $movement;
            }

            if ($issued !== []) {
                $this->bumpJobVersion($job);
            }

            return $issued;
        });
    }

    private function postIssue(
        VehicleServiceJob $job,
        VehicleServiceJobLine $line,
        int $warehouseId,
        int $warehouseLocationId,
        ?int $postedBy,
    ): InventoryMovement {
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
            batchId: $line->batch_id,
            unitCost: (string) $line->unit_cost,
            sourceType: VehicleServiceFinanceSource::JOB,
            sourceId: (int) $job->getKey(),
            sourceLineType: VehicleServiceFinanceSource::JOB_LINE,
            sourceLineId: (int) $line->getKey(),
            description: 'Vehicle service job '.$job->job_number,
            createdBy: $postedBy,
            uomId: $line->uom_id,
        ), $postedBy);

        $this->finance->postIssue($job, $line, $movement, $postedBy);
        $line->inventory_movement_id = $movement->getKey();
        $line->status = VehicleServiceLineStatus::Issued;
        $line->save();

        return $movement;
    }

    private function releaseReservation(
        VehicleServiceJob $job,
        VehicleServiceJobLine $line,
        ?int $actorId,
    ): void {
        $reservation = $this->activeReservation($job, $line);
        if ($reservation instanceof InventoryReservation) {
            $this->inventory->unreserve($reservation, null, $actorId);
        }
    }

    private function activeReservation(
        VehicleServiceJob $job,
        VehicleServiceJobLine $line,
    ): ?InventoryReservation {
        $reservations = InventoryReservation::query()
            ->where('tenant_id', $job->tenant_id)
            ->where('organization_unit_id', $job->organization_unit_id)
            ->where('source_type', VehicleServiceFinanceSource::JOB)
            ->where('source_id', $job->getKey())
            ->where('source_line_type', VehicleServiceFinanceSource::JOB_LINE)
            ->where('source_line_id', $line->getKey())
            ->whereIn('status', [ReservationStatus::Active->value, ReservationStatus::PartiallyAllocated->value])
            ->orderBy('id')
            ->get();

        if ($reservations->count() > 1) {
            throw new InvalidArgumentException('Multiple active stock reservations exist for one service job line.');
        }

        return $reservations->first();
    }

    /** @return array{WarehouseModel|null, WarehouseLocationModel|null} */
    private function automaticSource(VehicleServiceJob $job): array
    {
        $warehouse = $this->warehouseDefaults->resolveDefaultWarehouse(
            (int) $job->tenant_id,
            $job->organization_unit_id,
        );
        if (! $warehouse instanceof WarehouseModel && $job->organization_unit_id !== null) {
            $warehouse = $this->warehouseDefaults->resolveDefaultWarehouse((int) $job->tenant_id, null);
        }
        if (! $warehouse instanceof WarehouseModel) {
            $warehouses = WarehouseModel::query()
                ->forTenant((int) $job->tenant_id, $job->organization_unit_id)
                ->where('is_active', true)
                ->orderBy('id')
                ->limit(2)
                ->get();
            $warehouse = $warehouses->count() === 1 ? $warehouses->first() : null;
        }

        $location = $warehouse instanceof WarehouseModel
            ? $this->warehouseDefaults->resolveDefaultLocation($warehouse)
            : null;
        if ($warehouse instanceof WarehouseModel && ! $location instanceof WarehouseLocationModel) {
            $locations = $warehouse->locations()->where('is_active', true)->orderBy('id')->limit(2)->get();
            $location = $locations->count() === 1 ? $locations->first() : null;
        }

        return [$warehouse, $location];
    }

    private function assertInsideJobTransaction(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Vehicle service inventory changes require a locked job transaction.');
        }
    }
}
