<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Models\VehicleOwnership;
use Modules\Vehicle\Services\VehicleStatusService;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalAllocationStatus;
use Modules\VehicleRental\Enums\RentalDriverAssignmentStatus;
use Modules\VehicleRental\Enums\RentalVehicleSourceType;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalDriverAssignment;
use Modules\VehicleRental\Models\RentalVehicleAllocation;
use Modules\VehicleRental\Models\VehicleFinanceAgreement;

final class RentalAllocationService
{
    public function __construct(
        private readonly RentalNumberService $numbers,
        private readonly RentalAvailabilityService $availability,
        private readonly RentalReferenceValidator $references,
        private readonly VehicleStatusService $vehicleStatuses,
        private readonly RentalStatusHistoryService $history,
    ) {}

    public function create(RentalAgreement $agreement, array $data, ?int $userId): RentalVehicleAllocation
    {
        return DB::transaction(function () use ($agreement, $data, $userId): RentalVehicleAllocation {
            $agreement = RentalAgreement::query()->lockForUpdate()->findOrFail($agreement->getKey());
            if (! in_array($agreement->status, [RentalAgreementStatus::Draft, RentalAgreementStatus::Active], true)) {
                throw new InvalidArgumentException('Vehicle allocation requires a draft or active agreement.');
            }

            $from = CarbonImmutable::parse((string) $data['allocated_from']);
            $to = isset($data['allocated_to']) && $data['allocated_to'] !== null
                ? CarbonImmutable::parse((string) $data['allocated_to'])
                : CarbonImmutable::parse($agreement->ends_at);
            if (! $to->greaterThan($from)) {
                throw new InvalidArgumentException('Allocation end must be after its start.');
            }
            if ($from->lessThan(CarbonImmutable::parse($agreement->starts_at)) || $to->greaterThan(CarbonImmutable::parse($agreement->ends_at))) {
                throw new InvalidArgumentException('Allocation must be inside the agreement period.');
            }

            $sourceType = RentalVehicleSourceType::from((string) $data['vehicle_source_type']);
            $sourceAllocation = $this->validateSource($agreement, $data, $sourceType, $from, $to);
            $vehicle = $this->availability->assertVehicle(
                (int) $agreement->tenant_id,
                $agreement->organization_unit_id,
                (int) $data['vehicle_id'],
                $from->toDateTimeString(),
                $to->toDateTimeString(),
                allowedSourceAllocationId: $sourceAllocation?->getKey(),
            );
            $ownershipId = $this->resolveOwnership($agreement, $data, $sourceType, $sourceAllocation, $from, $to);

            $allocation = RentalVehicleAllocation::query()->create([
                'tenant_id' => $agreement->tenant_id,
                'organization_unit_id' => $agreement->organization_unit_id,
                'allocation_number' => $data['allocation_number'] ?? $this->numbers->next(
                    (int) $agreement->tenant_id,
                    $agreement->organization_unit_id,
                    'vehicle_rental_allocation',
                    'RVA-',
                ),
                'agreement_id' => $agreement->getKey(),
                'vehicle_id' => $vehicle->getKey(),
                'vehicle_ownership_id' => $ownershipId,
                'vehicle_source_type' => $sourceType->value,
                'source_allocation_id' => $sourceAllocation?->getKey(),
                'vehicle_finance_agreement_id' => $data['vehicle_finance_agreement_id'] ?? null,
                'replaces_allocation_id' => $data['replaces_allocation_id'] ?? null,
                'allocated_from' => $from,
                'allocated_to' => $to,
                'start_odometer' => $data['start_odometer'] ?? null,
                'status' => RentalAllocationStatus::Planned->value,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($data['drivers'] ?? [] as $driver) {
                $this->assignDriver($allocation, $driver, $userId);
            }

            $this->history->record($allocation, null, RentalAllocationStatus::Planned->value, $userId);

            return $allocation->refresh()->load($this->relations());
        });
    }

    public function assignDriver(RentalVehicleAllocation $allocation, array $data, ?int $userId): RentalDriverAssignment
    {
        return DB::transaction(function () use ($allocation, $data, $userId): RentalDriverAssignment {
            $allocation = RentalVehicleAllocation::query()->lockForUpdate()->findOrFail($allocation->getKey());
            $from = CarbonImmutable::parse((string) ($data['assigned_from'] ?? $allocation->allocated_from));
            $to = isset($data['assigned_to']) && $data['assigned_to'] !== null
                ? CarbonImmutable::parse((string) $data['assigned_to'])
                : CarbonImmutable::parse($allocation->allocated_to);
            if (! $to->greaterThan($from)) {
                throw new InvalidArgumentException('Driver assignment end must be after its start.');
            }
            if ($from->lessThan(CarbonImmutable::parse($allocation->allocated_from)) || $to->greaterThan(CarbonImmutable::parse($allocation->allocated_to))) {
                throw new InvalidArgumentException('Driver assignment must be inside the vehicle allocation period.');
            }

            $this->references->employee(
                (int) $data['employee_id'],
                (int) $allocation->tenant_id,
                $allocation->organization_unit_id,
            );

            $conflict = RentalDriverAssignment::query()
                ->forContext((int) $allocation->tenant_id, $allocation->organization_unit_id)
                ->where('employee_id', $data['employee_id'])
                ->whereIn('status', [RentalDriverAssignmentStatus::Planned->value, RentalDriverAssignmentStatus::Active->value])
                ->where('assigned_from', '<', $to)
                ->where(fn (Builder $query) => $query->whereNull('assigned_to')->orWhere('assigned_to', '>', $from))
                ->lockForUpdate()
                ->exists();
            if ($conflict) {
                throw new InvalidArgumentException('Driver is assigned to another rental during this period.');
            }

            if (($data['is_primary'] ?? true) === true) {
                $allocation->driverAssignments()
                    ->whereIn('status', [RentalDriverAssignmentStatus::Planned->value, RentalDriverAssignmentStatus::Active->value])
                    ->where('is_primary', true)
                    ->update(['is_primary' => false, 'updated_by' => $userId, 'updated_at' => now()]);
            }

            $assignment = $allocation->driverAssignments()->create([
                'tenant_id' => $allocation->tenant_id,
                'organization_unit_id' => $allocation->organization_unit_id,
                'agreement_id' => $allocation->agreement_id,
                'employee_id' => $data['employee_id'],
                'assignment_role' => $data['assignment_role'] ?? 'primary',
                'assigned_from' => $from,
                'assigned_to' => $to,
                'is_primary' => $data['is_primary'] ?? true,
                'status' => $allocation->status === RentalAllocationStatus::Active
                    ? RentalDriverAssignmentStatus::Active->value
                    : RentalDriverAssignmentStatus::Planned->value,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $allocation->row_version = (int) $allocation->row_version + 1;
            $allocation->updated_by = $userId;
            $allocation->save();

            return $assignment;
        });
    }

    public function activate(RentalVehicleAllocation $allocation, ?int $userId): RentalVehicleAllocation
    {
        return DB::transaction(function () use ($allocation, $userId): RentalVehicleAllocation {
            $allocation = RentalVehicleAllocation::query()->with('agreement')->lockForUpdate()->findOrFail($allocation->getKey());
            if ($allocation->status === RentalAllocationStatus::Active) {
                return $allocation->load($this->relations());
            }
            if ($allocation->status !== RentalAllocationStatus::Planned) {
                throw new InvalidArgumentException('Only a planned allocation can be activated.');
            }
            $this->availability->assertVehicle(
                (int) $allocation->tenant_id,
                $allocation->organization_unit_id,
                (int) $allocation->vehicle_id,
                $allocation->allocated_from->toDateTimeString(),
                $allocation->allocated_to->toDateTimeString(),
                excludeAllocationId: (int) $allocation->getKey(),
                allowedSourceAllocationId: $allocation->source_allocation_id,
            );

            $from = $allocation->status;
            $allocation->status = RentalAllocationStatus::Active;
            $allocation->activated_at = now();
            $allocation->row_version = (int) $allocation->row_version + 1;
            $allocation->updated_by = $userId;
            $allocation->save();
            $allocation->driverAssignments()->where('status', RentalDriverAssignmentStatus::Planned->value)
                ->update(['status' => RentalDriverAssignmentStatus::Active->value, 'updated_by' => $userId, 'updated_at' => now()]);
            $this->history->record($allocation, $from->value, RentalAllocationStatus::Active->value, $userId);

            if ($allocation->agreement->agreement_kind === RentalAgreementKind::CustomerRental) {
                $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($allocation->vehicle_id);
                if ($vehicle->status !== VehicleStatus::Rented) {
                    $this->vehicleStatuses->changeTo($vehicle, VehicleStatus::Rented, $userId, 'Activated rental allocation '.$allocation->allocation_number);
                }
            }

            return $allocation->refresh()->load($this->relations());
        });
    }

    public function close(
        RentalVehicleAllocation $allocation,
        RentalAllocationStatus $status,
        string $returnedAt,
        ?string $endOdometer,
        ?int $userId,
    ): RentalVehicleAllocation {
        if (! in_array($status, [RentalAllocationStatus::Returned, RentalAllocationStatus::Replaced, RentalAllocationStatus::Completed], true)) {
            throw new InvalidArgumentException('Invalid closing allocation status.');
        }

        return DB::transaction(function () use ($allocation, $status, $returnedAt, $endOdometer, $userId): RentalVehicleAllocation {
            $allocation = RentalVehicleAllocation::query()->with('agreement')->lockForUpdate()->findOrFail($allocation->getKey());
            if (! in_array($allocation->status, [RentalAllocationStatus::Planned, RentalAllocationStatus::Active], true)) {
                throw new InvalidArgumentException('Only a planned or active allocation can be closed.');
            }
            if ($endOdometer !== null && $allocation->start_odometer !== null && (float) $endOdometer < (float) $allocation->start_odometer) {
                throw new InvalidArgumentException('End odometer cannot be below the allocation start odometer.');
            }

            $from = $allocation->status;
            $allocation->status = $status;
            $allocation->actual_returned_at = $returnedAt;
            $currentEnd = CarbonImmutable::parse($allocation->allocated_to);
            $returnTime = CarbonImmutable::parse($returnedAt);
            $allocation->allocated_to = $returnTime->lessThan($currentEnd) ? $returnTime : $currentEnd;
            $allocation->end_odometer = $endOdometer;
            $allocation->closed_at = now();
            $allocation->row_version = (int) $allocation->row_version + 1;
            $allocation->updated_by = $userId;
            $allocation->save();
            $allocation->driverAssignments()
                ->whereIn('status', [RentalDriverAssignmentStatus::Planned->value, RentalDriverAssignmentStatus::Active->value])
                ->lockForUpdate()
                ->get()
                ->each(function (RentalDriverAssignment $assignment) use ($returnTime, $userId): void {
                    $plannedEnd = $assignment->assigned_to === null
                        ? $returnTime
                        : CarbonImmutable::parse($assignment->assigned_to);
                    $assignment->status = RentalDriverAssignmentStatus::Completed;
                    $assignment->assigned_to = $returnTime->lessThan($plannedEnd) ? $returnTime : $plannedEnd;
                    $assignment->updated_by = $userId;
                    $assignment->save();
                });
            $this->history->record($allocation, $from->value, $status->value, $userId);

            if ($allocation->agreement->agreement_kind === RentalAgreementKind::CustomerRental) {
                $otherActive = RentalVehicleAllocation::query()
                    ->where('vehicle_id', $allocation->vehicle_id)
                    ->whereKeyNot($allocation->getKey())
                    ->where('status', RentalAllocationStatus::Active->value)
                    ->whereHas('agreement', fn (Builder $query) => $query->where('agreement_kind', RentalAgreementKind::CustomerRental->value))
                    ->exists();
                if (! $otherActive) {
                    $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($allocation->vehicle_id);
                    if ($vehicle->status === VehicleStatus::Rented) {
                        $this->vehicleStatuses->changeTo($vehicle, VehicleStatus::Active, $userId, 'Rental allocation closed '.$allocation->allocation_number);
                    }
                }
            }

            return $allocation->refresh()->load($this->relations());
        });
    }

    public function paginate(int $tenantId, ?int $organizationUnitId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = RentalVehicleAllocation::query()->forContext($tenantId, $organizationUnitId)->with($this->relations());
        foreach (['agreement_id', 'vehicle_id', 'status', 'vehicle_source_type', 'source_allocation_id'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query->where($key, $filters[$key]);
            }
        }
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(fn (Builder $scope) => $scope
                ->where('allocation_number', 'like', "%{$search}%")
                ->orWhereHas('vehicle', fn (Builder $vehicle) => $vehicle
                    ->where('vehicle_number', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%"))
                ->orWhereHas('agreement', fn (Builder $agreement) => $agreement->where('agreement_number', 'like', "%{$search}%")));
        }

        return $query->latest('allocated_from')->latest('id')->paginate($perPage);
    }

    public function relations(): array
    {
        return [
            'agreement.customer', 'agreement.supplier', 'vehicle.make', 'vehicle.model', 'vehicle.category',
            'ownership', 'sourceAllocation.agreement.supplier', 'financeAgreement.supplier', 'driverAssignments.employee',
            'custodyEvents.items', 'replacementAllocation', 'replacesAllocation',
        ];
    }


    private function resolveOwnership(
        RentalAgreement $agreement,
        array $data,
        RentalVehicleSourceType $sourceType,
        ?RentalVehicleAllocation $sourceAllocation,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): ?int {
        if ($sourceAllocation !== null) {
            return $sourceAllocation->vehicle_ownership_id;
        }

        $ownershipId = isset($data['vehicle_ownership_id']) ? (int) $data['vehicle_ownership_id'] : null;
        if ($agreement->agreement_kind === RentalAgreementKind::OwnerSupply && $ownershipId === null) {
            throw new InvalidArgumentException('Owner supply allocation requires the matching vehicle ownership record.');
        }
        if ($ownershipId === null) {
            return null;
        }

        $ownership = VehicleOwnership::query()
            ->where('tenant_id', $agreement->tenant_id)
            ->where('vehicle_id', $data['vehicle_id'])
            ->whereKey($ownershipId)
            ->lockForUpdate()
            ->firstOrFail();
        if (CarbonImmutable::parse($ownership->started_at)->greaterThan($from)
            || ($ownership->ended_at !== null && CarbonImmutable::parse($ownership->ended_at)->lessThan($to))) {
            throw new InvalidArgumentException('Vehicle ownership does not cover the allocation period.');
        }
        if ($agreement->agreement_kind === RentalAgreementKind::OwnerSupply
            && ($ownership->owner_type !== VehicleOwnerType::Supplier
                || (int) $ownership->owner_id !== (int) $agreement->supplier_id)) {
            throw new InvalidArgumentException('Vehicle ownership must belong to the owner agreement supplier.');
        }
        if ($sourceType === RentalVehicleSourceType::CompanyOwned
            && $ownership->owner_type !== VehicleOwnerType::Company) {
            throw new InvalidArgumentException('Company-owned allocation requires a company vehicle ownership record.');
        }

        return (int) $ownership->getKey();
    }

    private function validateSource(
        RentalAgreement $agreement,
        array $data,
        RentalVehicleSourceType $sourceType,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): ?RentalVehicleAllocation {
        if ($agreement->agreement_kind === RentalAgreementKind::OwnerSupply) {
            if ($sourceType !== RentalVehicleSourceType::OwnerSupplied || ! empty($data['source_allocation_id'])) {
                throw new InvalidArgumentException('An owner supply agreement creates the source owner allocation directly.');
            }
            return null;
        }

        if ($agreement->agreement_kind !== RentalAgreementKind::CustomerRental) {
            throw new InvalidArgumentException('Unsupported rental agreement kind.');
        }

        if ($sourceType === RentalVehicleSourceType::OwnerSupplied) {
            $source = RentalVehicleAllocation::query()
                ->with('agreement')
                ->forContext((int) $agreement->tenant_id, $agreement->organization_unit_id)
                ->lockForUpdate()
                ->findOrFail((int) ($data['source_allocation_id'] ?? 0));
            if ($source->agreement->agreement_kind !== RentalAgreementKind::OwnerSupply
                || (int) $source->vehicle_id !== (int) $data['vehicle_id']
                || ! in_array($source->status, [RentalAllocationStatus::Planned, RentalAllocationStatus::Active], true)
                || CarbonImmutable::parse($source->allocated_from)->greaterThan($from)
                || CarbonImmutable::parse($source->allocated_to)->lessThan($to)) {
                throw new InvalidArgumentException('Owner source allocation does not cover the customer allocation.');
            }
            return $source;
        }

        if ($sourceType === RentalVehicleSourceType::Financed) {
            $finance = VehicleFinanceAgreement::query()
                ->forContext((int) $agreement->tenant_id, $agreement->organization_unit_id)
                ->where('vehicle_id', $data['vehicle_id'])
                ->whereKey($data['vehicle_finance_agreement_id'] ?? 0)
                ->where('status', 'active')
                ->firstOrFail();
            if (CarbonImmutable::parse($finance->starts_at)->greaterThan($from) || CarbonImmutable::parse($finance->matures_at)->lessThan($to)) {
                throw new InvalidArgumentException('Vehicle finance agreement does not cover the customer allocation.');
            }
        }

        return null;
    }
}
