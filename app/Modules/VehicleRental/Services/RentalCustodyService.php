<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalAllocationStatus;
use Modules\VehicleRental\Enums\RentalCustodyEventType;
use Modules\VehicleRental\Enums\RentalCustodyStatus;
use Modules\VehicleRental\Models\RentalCustodyEvent;
use Modules\VehicleRental\Models\RentalVehicleAllocation;
use Modules\VehicleRental\Models\RentalVehicleReplacement;

final class RentalCustodyService
{
    public function __construct(
        private readonly RentalNumberService $numbers,
        private readonly RentalAllocationService $allocations,
        private readonly RentalStatusHistoryService $history,
    ) {}

    public function create(RentalVehicleAllocation $allocation, array $data, ?int $userId): RentalCustodyEvent
    {
        return DB::transaction(function () use ($allocation, $data, $userId): RentalCustodyEvent {
            $allocation = RentalVehicleAllocation::query()->with('agreement')->lockForUpdate()->findOrFail($allocation->getKey());
            $this->assertAllocationExpectedVersion($allocation, (int) ($data['expected_allocation_version'] ?? 0));
            $eventType = RentalCustodyEventType::from((string) $data['event_type']);
            [$fromRole, $toRole] = $this->defaultRoles($eventType);
            if (isset($data['from_role']) && $data['from_role'] !== $fromRole) {
                throw new InvalidArgumentException('Custody from-role does not match the selected event type.');
            }
            if (isset($data['to_role']) && $data['to_role'] !== $toRole) {
                throw new InvalidArgumentException('Custody to-role does not match the selected event type.');
            }
            $this->assertEventAllowed(
                $allocation,
                $eventType,
                (string) $data['occurred_at'],
                (string) $data['odometer'],
                isset($data['replacement_id']) ? (int) $data['replacement_id'] : null,
            );

            $event = RentalCustodyEvent::query()->create([
                'tenant_id' => $allocation->tenant_id,
                'organization_unit_id' => $allocation->organization_unit_id,
                'event_number' => $data['event_number'] ?? $this->numbers->next(
                    (int) $allocation->tenant_id,
                    $allocation->organization_unit_id,
                    'vehicle_rental_custody',
                    'RCE-',
                ),
                'vehicle_allocation_id' => $allocation->getKey(),
                'replacement_id' => $data['replacement_id'] ?? null,
                'vehicle_id' => $allocation->vehicle_id,
                'event_type' => $eventType->value,
                'occurred_at' => $data['occurred_at'],
                'odometer' => $data['odometer'],
                'fuel_level_percent' => $data['fuel_level_percent'] ?? null,
                'location' => $data['location'] ?? null,
                'from_role' => $fromRole,
                'to_role' => $toRole,
                'handed_over_by_employee_id' => $data['handed_over_by_employee_id'] ?? null,
                'received_by_employee_id' => $data['received_by_employee_id'] ?? null,
                'external_handed_over_name' => $data['external_handed_over_name'] ?? null,
                'external_received_by_name' => $data['external_received_by_name'] ?? null,
                'condition_summary' => $data['condition_summary'] ?? null,
                'damage_summary' => $data['damage_summary'] ?? null,
                'status' => RentalCustodyStatus::Draft->value,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach (array_values($data['items'] ?? []) as $index => $item) {
                $event->items()->create([
                    'tenant_id' => $allocation->tenant_id,
                    'organization_unit_id' => $allocation->organization_unit_id,
                    'sequence' => $item['sequence'] ?? ($index + 1),
                    'item_type' => $item['item_type'],
                    'item_code' => $item['item_code'] ?? null,
                    'description' => $item['description'],
                    'expected_quantity' => $item['expected_quantity'] ?? null,
                    'actual_quantity' => $item['actual_quantity'] ?? null,
                    'condition_status' => $item['condition_status'] ?? null,
                    'is_chargeable' => $item['is_chargeable'] ?? false,
                    'estimated_amount' => $item['estimated_amount'] ?? '0.000000',
                    'responsible_side' => $item['responsible_side'] ?? null,
                    'remarks' => $item['remarks'] ?? null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }

            $this->history->record($event, null, RentalCustodyStatus::Draft->value, $userId);

            return $event->load($this->relations());
        });
    }

    public function confirm(RentalCustodyEvent $event, int $expectedVersion, ?int $userId): RentalCustodyEvent
    {
        return DB::transaction(function () use ($event, $expectedVersion, $userId): RentalCustodyEvent {
            $event = RentalCustodyEvent::query()->with('allocation.agreement')->lockForUpdate()->findOrFail($event->getKey());
            $this->assertExpectedVersion($event, $expectedVersion);
            if ($event->status === RentalCustodyStatus::Confirmed) {
                return $event->load($this->relations());
            }
            if ($event->status !== RentalCustodyStatus::Draft) {
                throw new InvalidArgumentException('Only a draft custody event can be confirmed.');
            }
            $allocation = RentalVehicleAllocation::query()
                ->with('agreement')
                ->lockForUpdate()
                ->findOrFail($event->vehicle_allocation_id);
            $this->lockCustodyTimeline($allocation);
            $event->setRelation('allocation', $allocation);
            $this->assertEventAllowed(
                $event->allocation,
                $event->event_type,
                $event->occurred_at->toDateTimeString(),
                (string) $event->odometer,
                $event->replacement_id,
                (int) $event->getKey(),
            );

            $previous = $event->status;
            $event->status = RentalCustodyStatus::Confirmed;
            $event->confirmed_by = $userId;
            $event->confirmed_at = now();
            $event->row_version = $expectedVersion + 1;
            $event->updated_by = $userId;
            $event->save();
            $this->history->record($event, $previous->value, RentalCustodyStatus::Confirmed->value, $userId);

            match ($event->event_type) {
                RentalCustodyEventType::OwnerToCompany => $this->activateOwnerAllocation($event, $userId),
                RentalCustodyEventType::CompanyToCustomer,
                RentalCustodyEventType::ReplacementIn => $this->activateCustomerAllocation($event, $userId),
                RentalCustodyEventType::CustomerToCompany,
                RentalCustodyEventType::ReplacementOut => $this->closeCustomerAllocation($event, $userId),
                RentalCustodyEventType::CompanyToOwner => $this->closeOwnerAllocation($event, $userId),
                RentalCustodyEventType::InternalTransfer => null,
            };

            return $event->refresh()->load($this->relations());
        });
    }

    public function reverse(RentalCustodyEvent $event, int $expectedVersion, ?int $userId, string $reason): RentalCustodyEvent
    {
        return DB::transaction(function () use ($event, $expectedVersion, $userId, $reason): RentalCustodyEvent {
            $event = RentalCustodyEvent::query()->lockForUpdate()->findOrFail($event->getKey());
            $this->assertExpectedVersion($event, $expectedVersion);
            if ($event->status !== RentalCustodyStatus::Confirmed) {
                throw new InvalidArgumentException('Only a confirmed custody event can be reversed.');
            }
            if ($event->event_type !== RentalCustodyEventType::InternalTransfer) {
                throw new InvalidArgumentException('Operational custody reversals require a compensating event to protect allocation and vehicle history.');
            }
            $event->status = RentalCustodyStatus::Reversed;
            $event->reversed_by = $userId;
            $event->reversed_at = now();
            $event->reversal_reason = $reason;
            $event->row_version = $expectedVersion + 1;
            $event->updated_by = $userId;
            $event->save();
            $this->history->record($event, RentalCustodyStatus::Confirmed->value, RentalCustodyStatus::Reversed->value, $userId, $reason);

            return $event->refresh()->load($this->relations());
        });
    }

    public function paginate(int $tenantId, ?int $organizationUnitId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = RentalCustodyEvent::query()->forContext($tenantId, $organizationUnitId)->with($this->relations());
        foreach (['vehicle_allocation_id', 'vehicle_id', 'event_type', 'status', 'replacement_id'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query->where($key, $filters[$key]);
            }
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('occurred_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('occurred_at', '<=', $filters['date_to']);
        }

        return $query->latest('occurred_at')->latest('id')->paginate($perPage);
    }

    public function relations(): array
    {
        return [
            'allocation.agreement.customer', 'allocation.agreement.supplier', 'vehicle.make', 'vehicle.model',
            'items', 'handedOverByEmployee', 'receivedByEmployee', 'replacement',
        ];
    }

    private function lockCustodyTimeline(RentalVehicleAllocation $allocation): void
    {
        RentalCustodyEvent::query()
            ->where('tenant_id', $allocation->tenant_id)
            ->where('vehicle_allocation_id', $allocation->getKey())
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);
    }

    private function assertEventAllowed(
        RentalVehicleAllocation $allocation,
        RentalCustodyEventType $eventType,
        string $occurredAt,
        string $odometer,
        ?int $replacementId = null,
        ?int $excludeEventId = null,
    ): void {
        $occurred = CarbonImmutable::parse($occurredAt);
        if ($occurred->lessThan(CarbonImmutable::parse($allocation->allocated_from))
            || ($allocation->allocated_to !== null && $occurred->greaterThan(CarbonImmutable::parse($allocation->allocated_to)))) {
            throw new InvalidArgumentException('Custody event must be inside the allocation period.');
        }

        $customerAgreement = $allocation->agreement->agreement_kind === RentalAgreementKind::CustomerRental;
        $ownerAgreement = $allocation->agreement->agreement_kind === RentalAgreementKind::OwnerSupply;
        if ($customerAgreement && ! in_array($eventType, [
            RentalCustodyEventType::CompanyToCustomer,
            RentalCustodyEventType::CustomerToCompany,
            RentalCustodyEventType::ReplacementOut,
            RentalCustodyEventType::ReplacementIn,
            RentalCustodyEventType::InternalTransfer,
        ], true)) {
            throw new InvalidArgumentException('Custody event type is not valid for a customer rental allocation.');
        }
        if ($ownerAgreement && ! in_array($eventType, [
            RentalCustodyEventType::OwnerToCompany,
            RentalCustodyEventType::CompanyToOwner,
            RentalCustodyEventType::InternalTransfer,
        ], true)) {
            throw new InvalidArgumentException('Custody event type is not valid for an owner supply allocation.');
        }

        $confirmed = $allocation->custodyEvents()
            ->where('status', RentalCustodyStatus::Confirmed->value)
            ->when($excludeEventId !== null, fn (Builder $query) => $query->whereKeyNot($excludeEventId))
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        $latest = $confirmed->last();
        if ($latest !== null) {
            if ($occurred->lessThan(CarbonImmutable::parse($latest->occurred_at))) {
                throw new InvalidArgumentException('Custody events must be recorded in chronological order.');
            }
            if ((float) $odometer < (float) $latest->odometer) {
                throw new InvalidArgumentException('Custody-event odometer cannot be below the previous confirmed event.');
            }
        }

        $replacementEvent = in_array($eventType, [
            RentalCustodyEventType::ReplacementOut,
            RentalCustodyEventType::ReplacementIn,
        ], true);
        if ($replacementEvent && $replacementId === null) {
            throw new InvalidArgumentException('Replacement custody events require a replacement reference.');
        }
        if (! $replacementEvent && $replacementId !== null) {
            throw new InvalidArgumentException('Only replacement custody events may reference a replacement.');
        }
        if ($replacementEvent && $replacementId !== null) {
            $this->assertReplacementEventMatchesAllocation($allocation, $eventType, $replacementId);
        }

        $lastOperational = $confirmed->last(
            fn (RentalCustodyEvent $item) => $item->event_type !== RentalCustodyEventType::InternalTransfer,
        );

        if ($eventType === RentalCustodyEventType::InternalTransfer) {
            if ($lastOperational !== null && in_array($lastOperational->event_type, [
                RentalCustodyEventType::CustomerToCompany,
                RentalCustodyEventType::ReplacementOut,
                RentalCustodyEventType::CompanyToOwner,
            ], true)) {
                throw new InvalidArgumentException('Internal transfer is not allowed after custody has been closed.');
            }

            return;
        }

        if ($customerAgreement) {
            $expected = match ($lastOperational?->event_type) {
                null => [RentalCustodyEventType::CompanyToCustomer, RentalCustodyEventType::ReplacementIn],
                RentalCustodyEventType::CompanyToCustomer,
                RentalCustodyEventType::ReplacementIn => [RentalCustodyEventType::CustomerToCompany, RentalCustodyEventType::ReplacementOut],
                default => [],
            };
            if (! in_array($eventType, $expected, true)) {
                throw new InvalidArgumentException('Customer custody event does not follow the required handover/return sequence.');
            }

            return;
        }

        if ($ownerAgreement) {
            $expected = match ($lastOperational?->event_type) {
                null => [RentalCustodyEventType::OwnerToCompany],
                RentalCustodyEventType::OwnerToCompany => [RentalCustodyEventType::CompanyToOwner],
                default => [],
            };
            if (! in_array($eventType, $expected, true)) {
                throw new InvalidArgumentException('Owner custody event does not follow the required receive/return sequence.');
            }
            if ($eventType === RentalCustodyEventType::CompanyToOwner) {
                $openCustomerAllocation = RentalVehicleAllocation::query()
                    ->forContext((int) $allocation->tenant_id, $allocation->organization_unit_id)
                    ->where('source_allocation_id', $allocation->getKey())
                    ->whereIn('status', [RentalAllocationStatus::Planned->value, RentalAllocationStatus::Active->value])
                    ->exists();
                if ($openCustomerAllocation) {
                    throw new InvalidArgumentException('Vehicle cannot be returned to its owner while a customer allocation is planned or active.');
                }
            }
        }
    }

    private function assertReplacementEventMatchesAllocation(
        RentalVehicleAllocation $allocation,
        RentalCustodyEventType $eventType,
        int $replacementId,
    ): void {
        $replacement = RentalVehicleReplacement::query()
            ->where('tenant_id', $allocation->tenant_id)
            ->lockForUpdate()
            ->findOrFail($replacementId);

        if ((int) $replacement->agreement_id !== (int) $allocation->agreement_id) {
            throw new InvalidArgumentException('Replacement custody event does not belong to this rental agreement.');
        }

        if ($eventType === RentalCustodyEventType::ReplacementOut
            && (int) $replacement->old_allocation_id !== (int) $allocation->getKey()) {
            throw new InvalidArgumentException('Replacement return event does not belong to this allocation.');
        }

        if ($eventType === RentalCustodyEventType::ReplacementIn
            && (int) $replacement->new_allocation_id !== (int) $allocation->getKey()) {
            throw new InvalidArgumentException('Replacement handover event does not belong to this allocation.');
        }
    }

    /** @return array{0:string,1:string} */
    private function defaultRoles(RentalCustodyEventType $eventType): array
    {
        return match ($eventType) {
            RentalCustodyEventType::OwnerToCompany => ['owner', 'company'],
            RentalCustodyEventType::CompanyToCustomer, RentalCustodyEventType::ReplacementIn => ['company', 'customer'],
            RentalCustodyEventType::CustomerToCompany, RentalCustodyEventType::ReplacementOut => ['customer', 'company'],
            RentalCustodyEventType::CompanyToOwner => ['company', 'owner'],
            RentalCustodyEventType::InternalTransfer => ['company', 'company'],
        };
    }

    private function activateOwnerAllocation(RentalCustodyEvent $event, ?int $userId): void
    {
        $allocation = $this->allocations->activate($event->allocation, $userId);
        if ($allocation->start_odometer === null) {
            $allocation->forceFill([
                'start_odometer' => $event->odometer,
                'row_version' => (int) $allocation->row_version + 1,
                'updated_by' => $userId,
            ])->save();
        }
    }

    private function activateCustomerAllocation(RentalCustodyEvent $event, ?int $userId): void
    {
        if ($event->allocation->source_allocation_id !== null) {
            $sourceReceived = RentalCustodyEvent::query()
                ->where('vehicle_allocation_id', $event->allocation->source_allocation_id)
                ->where('event_type', RentalCustodyEventType::OwnerToCompany->value)
                ->where('status', RentalCustodyStatus::Confirmed->value)
                ->where('occurred_at', '<=', $event->occurred_at)
                ->exists();
            if (! $sourceReceived) {
                throw new InvalidArgumentException('Owner-supplied vehicle must be received by the company before customer handover.');
            }
        }
        $allocation = $this->allocations->activate($event->allocation, $userId);
        if ($allocation->start_odometer === null) {
            $allocation->forceFill([
                'start_odometer' => $event->odometer,
                'row_version' => (int) $allocation->row_version + 1,
                'updated_by' => $userId,
            ])->save();
        }
    }

    private function closeCustomerAllocation(RentalCustodyEvent $event, ?int $userId): void
    {
        $status = $event->event_type === RentalCustodyEventType::ReplacementOut
            ? RentalAllocationStatus::Replaced
            : RentalAllocationStatus::Returned;
        $this->allocations->close($event->allocation, $status, $event->occurred_at->toDateTimeString(), (string) $event->odometer, $userId);
    }

    private function closeOwnerAllocation(RentalCustodyEvent $event, ?int $userId): void
    {
        $this->allocations->close($event->allocation, RentalAllocationStatus::Returned, $event->occurred_at->toDateTimeString(), (string) $event->odometer, $userId);
    }

    private function assertExpectedVersion(RentalCustodyEvent $event, int $expectedVersion): void
    {
        if ((int) $event->row_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'expected_version' => ['The rental custody event changed after it was loaded. Reload and review the latest version.'],
            ]);
        }
    }

    private function assertAllocationExpectedVersion(RentalVehicleAllocation $allocation, int $expectedVersion): void
    {
        if ((int) $allocation->row_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'expected_allocation_version' => ['The vehicle allocation changed after it was loaded. Reload and review the latest version.'],
            ]);
        }
    }
}
