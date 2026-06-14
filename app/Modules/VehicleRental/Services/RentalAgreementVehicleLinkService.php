<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\VehicleRental\DTOs\RentalAgreementVehicleLinkData;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalAgreementVehicleLinkStatus;
use Modules\VehicleRental\Models\RentalAgreementVehicle;
use Modules\VehicleRental\Models\RentalAgreementVehicleLink;

final class RentalAgreementVehicleLinkService
{
    public function create(
        int $tenantId,
        ?int $organizationUnitId,
        RentalAgreementVehicleLinkData $data,
    ): RentalAgreementVehicleLink {
        return DB::transaction(function () use ($tenantId, $organizationUnitId, $data): RentalAgreementVehicleLink {
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

            $conflict = RentalAgreementVehicleLink::query()
                ->where('tenant_id', $tenantId)
                ->where('vehicle_id', $inbound->vehicle_id)
                ->where('status', RentalAgreementVehicleLinkStatus::Active->value)
                ->where('effective_from', '<', $to)
                ->where('effective_to', '>', $from)
                ->lockForUpdate()
                ->exists();
            if ($conflict) {
                throw new InvalidArgumentException(
                    'The vehicle already has an active inbound/outbound allocation link during this period.',
                );
            }

            return RentalAgreementVehicleLink::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'vehicle_id' => $inbound->vehicle_id,
                'inbound_agreement_id' => $inbound->agreement_id,
                'inbound_agreement_vehicle_id' => $inbound->getKey(),
                'outbound_agreement_id' => $outbound->agreement_id,
                'outbound_agreement_vehicle_id' => $outbound->getKey(),
                'effective_from' => $from,
                'effective_to' => $to,
                'status' => RentalAgreementVehicleLinkStatus::Active->value,
                'remarks' => $data->remarks,
                'created_by' => $data->createdBy,
                'approved_by' => $data->createdBy,
                'approved_at' => now(),
            ])->load([
                'vehicle.make',
                'vehicle.model',
                'inboundAgreement.supplier',
                'inboundAllocation',
                'outboundAgreement.customer',
                'outboundAllocation',
            ]);
        });
    }

    public function cancel(RentalAgreementVehicleLink $link, ?int $changedBy, ?string $reason): RentalAgreementVehicleLink
    {
        return DB::transaction(function () use ($link, $changedBy, $reason): RentalAgreementVehicleLink {
            $link = RentalAgreementVehicleLink::query()->lockForUpdate()->findOrFail($link->getKey());
            if ($link->status === RentalAgreementVehicleLinkStatus::Cancelled) {
                return $link;
            }
            if ($link->usageContexts()->whereHas('usageLog', fn (Builder $query) => $query
                ->whereIn('status', ['approved', 'submitted']))->exists()) {
                throw new InvalidArgumentException(
                    'A rental allocation link used by submitted or approved running charts cannot be cancelled.',
                );
            }

            $link->forceFill([
                'status' => RentalAgreementVehicleLinkStatus::Cancelled->value,
                'remarks' => $reason === null
                    ? $link->remarks
                    : trim((string) $link->remarks."\nCancelled by {$changedBy}: {$reason}"),
            ])->save();

            return $link->refresh();
        });
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
}
