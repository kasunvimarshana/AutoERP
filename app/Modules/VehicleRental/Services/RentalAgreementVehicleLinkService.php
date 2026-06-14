<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\DTOs\RentalAgreementVehicleLinkData;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalAgreementVehicleLinkStatus;
use Modules\VehicleRental\Models\RentalAgreementVehicle;
use Modules\VehicleRental\Models\RentalAgreementVehicleLink;
use Modules\VehicleRental\Models\RentalStatusHistory;

final class RentalAgreementVehicleLinkService
{
    public function create(
        int $tenantId,
        ?int $organizationUnitId,
        RentalAgreementVehicleLinkData $data,
    ): RentalAgreementVehicleLink {
        return DB::transaction(function () use ($tenantId, $organizationUnitId, $data): RentalAgreementVehicleLink {
            $vehicleId = RentalAgreementVehicle::query()
                ->forContext($tenantId, $organizationUnitId)
                ->whereKey($data->inboundAgreementVehicleId)
                ->value('vehicle_id');
            if ($vehicleId === null) {
                throw new InvalidArgumentException('Inbound rental allocation was not found in the active context.');
            }
            Vehicle::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($vehicleId)
                ->lockForUpdate()
                ->firstOrFail();
            $allocations = RentalAgreementVehicle::query()
                ->forContext($tenantId, $organizationUnitId)
                ->whereKey([
                    $data->inboundAgreementVehicleId,
                    $data->outboundAgreementVehicleId,
                ])
                ->with('agreement.rateSnapshot')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (RentalAgreementVehicle $allocation): int => (int) $allocation->getKey());

            $inbound = $allocations->get($data->inboundAgreementVehicleId)
                ?? throw new InvalidArgumentException('Inbound rental allocation was not found in the active context.');
            $outbound = $allocations->get($data->outboundAgreementVehicleId)
                ?? throw new InvalidArgumentException('Outbound rental allocation was not found in the active context.');

            $this->validateAllocation($inbound, RentalAgreementDirection::Inbound);
            $this->validateAllocation($outbound, RentalAgreementDirection::Outbound);
            if ((int) $inbound->vehicle_id !== (int) $outbound->vehicle_id) {
                throw new InvalidArgumentException('Linked inbound and outbound allocations must reference the same vehicle.');
            }

            $from = CarbonImmutable::parse($data->effectiveFrom);
            $to = CarbonImmutable::parse($data->effectiveTo);
            if ($to->lessThanOrEqualTo($from)) {
                throw new InvalidArgumentException('Rental allocation link end must be after its start.');
            }
            $this->assertInsideAllocation($inbound, $from, $to, 'Inbound');
            $this->assertInsideAllocation($outbound, $from, $to, 'Outbound');

            $this->assertNoConflict($tenantId, $organizationUnitId, (int) $inbound->vehicle_id, $from, $to);

            $link = RentalAgreementVehicleLink::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'vehicle_id' => $inbound->vehicle_id,
                'inbound_agreement_id' => $inbound->agreement_id,
                'inbound_agreement_vehicle_id' => $inbound->getKey(),
                'outbound_agreement_id' => $outbound->agreement_id,
                'outbound_agreement_vehicle_id' => $outbound->getKey(),
                'effective_from' => $from,
                'effective_to' => $to,
                'status' => RentalAgreementVehicleLinkStatus::Draft->value,
                'remarks' => $data->remarks,
                'created_by' => $data->createdBy,
            ]);
            $this->recordStatus($link, null, RentalAgreementVehicleLinkStatus::Draft, $data->createdBy);

            return $link->load([
                'vehicle.make',
                'vehicle.model',
                'inboundAgreement.supplier',
                'inboundAllocation',
                'outboundAgreement.customer',
                'outboundAllocation',
            ]);
        });
    }

    public function submit(
        RentalAgreementVehicleLink $link,
        ?int $changedBy,
        ?string $reason = null,
    ): RentalAgreementVehicleLink {
        return $this->transition(
            $link,
            RentalAgreementVehicleLinkStatus::Draft,
            RentalAgreementVehicleLinkStatus::Submitted,
            $changedBy,
            $reason,
        );
    }

    public function approve(
        RentalAgreementVehicleLink $link,
        ?int $changedBy,
        ?string $reason = null,
    ): RentalAgreementVehicleLink {
        return $this->transition(
            $link,
            RentalAgreementVehicleLinkStatus::Submitted,
            RentalAgreementVehicleLinkStatus::Approved,
            $changedBy,
            $reason,
        );
    }

    public function cancel(RentalAgreementVehicleLink $link, ?int $changedBy, ?string $reason): RentalAgreementVehicleLink
    {
        return DB::transaction(function () use ($link, $changedBy, $reason): RentalAgreementVehicleLink {
            $link = RentalAgreementVehicleLink::query()->lockForUpdate()->findOrFail($link->getKey());
            if ($link->status === RentalAgreementVehicleLinkStatus::Cancelled) {
                return $link;
            }
            if ($link->usageContexts()->exists()) {
                throw new InvalidArgumentException(
                    'A rental allocation link used by a running chart cannot be cancelled; supersede it prospectively.',
                );
            }

            $old = $link->status;
            $link->forceFill([
                'status' => RentalAgreementVehicleLinkStatus::Cancelled->value,
                'cancelled_by' => $changedBy,
                'cancelled_at' => now(),
                'remarks' => $reason === null
                    ? $link->remarks
                    : trim((string) $link->remarks."\nCancelled by {$changedBy}: {$reason}"),
            ])->save();
            $this->recordStatus($link, $old, RentalAgreementVehicleLinkStatus::Cancelled, $changedBy, $reason);

            return $link->refresh();
        });
    }

    public function supersedeForReplacement(
        RentalAgreementVehicle $allocation,
        CarbonImmutable $at,
        ?int $changedBy,
    ): void {
        $links = RentalAgreementVehicleLink::query()
            ->where(function (Builder $query) use ($allocation): void {
                $query->where('inbound_agreement_vehicle_id', $allocation->getKey())
                    ->orWhere('outbound_agreement_vehicle_id', $allocation->getKey());
            })
            ->whereIn('status', [
                RentalAgreementVehicleLinkStatus::Draft->value,
                RentalAgreementVehicleLinkStatus::Submitted->value,
                RentalAgreementVehicleLinkStatus::Approved->value,
            ])
            ->where('effective_to', '>', $at)
            ->lockForUpdate()
            ->get();

        foreach ($links as $link) {
            if ($link->effective_from->greaterThanOrEqualTo($at) && $link->usageContexts()->exists()) {
                throw new InvalidArgumentException(
                    'Vehicle replacement cannot invalidate a rental link already used by running charts.',
                );
            }
            $old = $link->status;
            $updates = ['status' => RentalAgreementVehicleLinkStatus::Superseded->value];
            if ($link->effective_from->lessThan($at)) {
                $updates['effective_to'] = $at;
            }
            $link->forceFill($updates)->save();
            $this->recordStatus(
                $link,
                $old,
                RentalAgreementVehicleLinkStatus::Superseded,
                $changedBy,
                'Vehicle allocation replaced.',
            );
        }
    }

    private function validateAllocation(
        RentalAgreementVehicle $allocation,
        RentalAgreementDirection $direction,
    ): void {
        $agreement = $allocation->agreement
            ?? throw new InvalidArgumentException('Rental allocation agreement is missing.');
        if ($agreement->direction !== $direction) {
            throw new InvalidArgumentException(
                ucfirst($direction->value).' allocation must belong to an '.$direction->value.' agreement.',
            );
        }
        if (! in_array($agreement->status, [
            RentalAgreementStatus::Draft,
            RentalAgreementStatus::Confirmed,
            RentalAgreementStatus::Active,
            RentalAgreementStatus::Returned,
        ], true)) {
            throw new InvalidArgumentException(
                'Only confirmed, active, or returned agreements can participate in an allocation link.',
            );
        }
        if ($agreement->rateSnapshot === null) {
            throw new InvalidArgumentException('Linked agreements require immutable rate snapshots.');
        }
    }

    private function assertInsideAllocation(
        RentalAgreementVehicle $allocation,
        CarbonImmutable $from,
        CarbonImmutable $to,
        string $label,
    ): void {
        $allocationTo = $allocation->allocated_to ?? $allocation->agreement?->expected_end_at;
        if ($from->lessThan($allocation->allocated_from)
            || $allocationTo === null
            || $to->greaterThan($allocationTo)) {
            throw new InvalidArgumentException(
                "{$label} link period must be inside the corresponding vehicle allocation period.",
            );
        }
    }

    private function transition(
        RentalAgreementVehicleLink $link,
        RentalAgreementVehicleLinkStatus $expected,
        RentalAgreementVehicleLinkStatus $target,
        ?int $changedBy,
        ?string $reason,
    ): RentalAgreementVehicleLink {
        return DB::transaction(function () use ($link, $expected, $target, $changedBy, $reason): RentalAgreementVehicleLink {
            $link = RentalAgreementVehicleLink::query()->lockForUpdate()->findOrFail($link->getKey());
            Vehicle::query()
                ->whereKey($link->vehicle_id)
                ->lockForUpdate()
                ->firstOrFail();
            $allocations = RentalAgreementVehicle::query()
                ->whereKey([
                    $link->inbound_agreement_vehicle_id,
                    $link->outbound_agreement_vehicle_id,
                ])
                ->with('agreement.rateSnapshot')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (RentalAgreementVehicle $allocation): int => (int) $allocation->getKey());

            if ($link->status !== $expected) {
                throw new InvalidArgumentException(
                    "Rental allocation link must be {$expected->value} before it can become {$target->value}.",
                );
            }
            $inbound = $allocations->get((int) $link->inbound_agreement_vehicle_id)
                ?? throw new InvalidArgumentException('Inbound rental allocation is missing.');
            $outbound = $allocations->get((int) $link->outbound_agreement_vehicle_id)
                ?? throw new InvalidArgumentException('Outbound rental allocation is missing.');
            $this->validateAllocation($inbound, RentalAgreementDirection::Inbound);
            $this->validateAllocation($outbound, RentalAgreementDirection::Outbound);
            if ($target === RentalAgreementVehicleLinkStatus::Approved) {
                foreach ([$inbound, $outbound] as $allocation) {
                    if (! in_array($allocation->agreement?->status, [
                        RentalAgreementStatus::Confirmed,
                        RentalAgreementStatus::Active,
                        RentalAgreementStatus::Returned,
                    ], true)) {
                        throw new InvalidArgumentException(
                            'Both agreements must be confirmed, active, or returned before link approval.',
                        );
                    }
                }
            }
            $this->assertNoConflict(
                (int) $link->tenant_id,
                $link->organization_unit_id,
                (int) $link->vehicle_id,
                $link->effective_from->toImmutable(),
                $link->effective_to->toImmutable(),
                (int) $link->getKey(),
            );

            $updates = ['status' => $target->value];
            if ($target === RentalAgreementVehicleLinkStatus::Submitted) {
                $updates['submitted_by'] = $changedBy;
                $updates['submitted_at'] = now();
            }
            if ($target === RentalAgreementVehicleLinkStatus::Approved) {
                $updates['approved_by'] = $changedBy;
                $updates['approved_at'] = now();
            }
            $link->forceFill($updates)->save();
            $this->recordStatus($link, $expected, $target, $changedBy, $reason);

            return $link->refresh()->load([
                'vehicle.make',
                'vehicle.model',
                'inboundAgreement.supplier',
                'inboundAllocation',
                'outboundAgreement.customer',
                'outboundAllocation',
            ]);
        });
    }

    private function assertNoConflict(
        int $tenantId,
        ?int $organizationUnitId,
        int $vehicleId,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $exceptId = null,
    ): void {
        $conflict = RentalAgreementVehicleLink::query()
            ->forContext($tenantId, $organizationUnitId)
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', [
                RentalAgreementVehicleLinkStatus::Draft->value,
                RentalAgreementVehicleLinkStatus::Submitted->value,
                RentalAgreementVehicleLinkStatus::Approved->value,
            ])
            ->when($exceptId !== null, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->where('effective_from', '<', $to)
            ->where('effective_to', '>', $from)
            ->lockForUpdate()
            ->exists();
        if ($conflict) {
            throw new InvalidArgumentException(
                'The vehicle already has a conflicting open inbound/outbound allocation link during this period.',
            );
        }
    }

    private function recordStatus(
        RentalAgreementVehicleLink $link,
        ?RentalAgreementVehicleLinkStatus $old,
        RentalAgreementVehicleLinkStatus $new,
        ?int $changedBy,
        ?string $reason = null,
    ): void {
        RentalStatusHistory::query()->create([
            'tenant_id' => $link->tenant_id,
            'organization_unit_id' => $link->organization_unit_id,
            'agreement_vehicle_link_id' => $link->getKey(),
            'entity_type' => 'vehicle_link',
            'subject_id' => $link->getKey(),
            'old_status' => $old?->value,
            'new_status' => $new->value,
            'reason' => $reason,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }
}
