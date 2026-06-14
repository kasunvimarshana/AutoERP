<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalAgreementVehicleLinkStatus;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAgreementVehicle;
use Modules\VehicleRental\Models\RentalAgreementVehicleLink;
use Modules\VehicleRental\Models\RentalUsageContext;
use Modules\VehicleRental\Models\RentalUsageLog;

final class RentalUsageContextService
{
    /**
     * @return Collection<int, RentalUsageContext>
     */
    public function attach(
        RentalUsageLog $usageLog,
        RentalAgreement $selectedAgreement,
        RentalAgreementVehicle $selectedAllocation,
        string $usageDate,
        ?string $startTime,
    ): Collection {
        $resolved = $this->resolve($selectedAgreement, $selectedAllocation, $usageDate, $startTime);
        $contexts = collect();
        foreach ($resolved['contexts'] as $context) {
            $agreement = $context['agreement'];
            $allocation = $context['allocation'];
            $contexts->push(RentalUsageContext::query()->create([
                'tenant_id' => $usageLog->tenant_id,
                'organization_unit_id' => $usageLog->organization_unit_id,
                'usage_log_id' => $usageLog->getKey(),
                'agreement_id' => $agreement->getKey(),
                'agreement_vehicle_id' => $allocation->getKey(),
                'agreement_vehicle_link_id' => $resolved['link']?->getKey(),
                'rate_snapshot_id' => $agreement->rateSnapshot?->getKey(),
                'agreement_direction' => $agreement->direction->value,
                'financial_side' => RentalFinancialSide::fromDirection($agreement->direction)->value,
                'party_type' => $agreement->party_type->value,
                'party_id' => $agreement->party_id,
            ]));
        }

        return $contexts;
    }

    /**
     * @return array{
     *   selected_agreement: RentalAgreement,
     *   selected_allocation: RentalAgreementVehicle,
     *   link: RentalAgreementVehicleLink|null,
     *   contexts: list<array{agreement: RentalAgreement, allocation: RentalAgreementVehicle}>
     * }
     */
    public function resolve(
        RentalAgreement $selectedAgreement,
        RentalAgreementVehicle $selectedAllocation,
        string $usageDate,
        ?string $startTime = null,
    ): array {
        if ((int) $selectedAllocation->agreement_id !== (int) $selectedAgreement->getKey()) {
            throw new InvalidArgumentException('Selected vehicle allocation does not belong to the agreement.');
        }
        if ((int) $selectedAllocation->vehicle_id < 1) {
            throw new InvalidArgumentException('Selected vehicle allocation is invalid.');
        }

        $usageDay = CarbonImmutable::parse($usageDate)->startOfDay();
        $at = $startTime === null
            ? $usageDay
            : CarbonImmutable::parse($usageDate.' '.$startTime);
        $selectedAgreement->loadMissing(['rateSnapshot', 'customer', 'supplier']);
        $this->assertEligible($selectedAgreement, $selectedAllocation, $at, $startTime === null);

        $links = RentalAgreementVehicleLink::query()
            ->forContext((int) $selectedAgreement->tenant_id, $selectedAgreement->organization_unit_id)
            ->where('vehicle_id', $selectedAllocation->vehicle_id)
            ->where('status', RentalAgreementVehicleLinkStatus::Approved->value)
            ->when(
                $startTime === null,
                fn ($query) => $query
                    ->where('effective_from', '<=', $usageDay->endOfDay())
                    ->where('effective_to', '>=', $usageDay),
                fn ($query) => $query
                    ->where('effective_from', '<=', $at)
                    ->where('effective_to', '>=', $at),
            )
            ->where(function ($query) use ($selectedAllocation): void {
                $query->where('inbound_agreement_vehicle_id', $selectedAllocation->getKey())
                    ->orWhere('outbound_agreement_vehicle_id', $selectedAllocation->getKey());
            })
            ->where(function ($query) use ($selectedAgreement, $selectedAllocation): void {
                if ($selectedAgreement->direction === RentalAgreementDirection::Inbound) {
                    $query->where('inbound_agreement_id', $selectedAgreement->getKey())
                        ->where('inbound_agreement_vehicle_id', $selectedAllocation->getKey());
                } else {
                    $query->where('outbound_agreement_id', $selectedAgreement->getKey())
                        ->where('outbound_agreement_vehicle_id', $selectedAllocation->getKey());
                }
            })
            ->with([
                'inboundAgreement.rateSnapshot',
                'inboundAgreement.supplier',
                'inboundAllocation',
                'outboundAgreement.rateSnapshot',
                'outboundAgreement.customer',
                'outboundAllocation',
            ])
            ->when(DB::transactionLevel() > 0, fn ($query) => $query->lockForUpdate())
            ->get();

        if ($links->count() > 1) {
            throw new InvalidArgumentException(
                'The selected agreement resolves to ambiguous rental allocation links for this usage time.',
            );
        }

        $link = $links->first();
        $contexts = [[
            'agreement' => $selectedAgreement,
            'allocation' => $selectedAllocation,
        ]];
        if ($link !== null) {
            $counterpartAgreement = $selectedAgreement->direction === RentalAgreementDirection::Outbound
                ? $link->inboundAgreement
                : $link->outboundAgreement;
            $counterpartAllocation = $selectedAgreement->direction === RentalAgreementDirection::Outbound
                ? $link->inboundAllocation
                : $link->outboundAllocation;
            if ($counterpartAgreement === null || $counterpartAllocation === null) {
                throw new InvalidArgumentException('Linked counterpart agreement context is incomplete.');
            }
            $this->assertEligible($counterpartAgreement, $counterpartAllocation, $at, $startTime === null);
            $contexts[] = [
                'agreement' => $counterpartAgreement,
                'allocation' => $counterpartAllocation,
            ];
        }

        return [
            'selected_agreement' => $selectedAgreement,
            'selected_allocation' => $selectedAllocation,
            'link' => $link,
            'contexts' => $contexts,
        ];
    }

    private function assertEligible(
        RentalAgreement $agreement,
        RentalAgreementVehicle $allocation,
        CarbonImmutable $at,
        bool $dateOnly,
    ): void {
        if (! in_array($agreement->status, [
            RentalAgreementStatus::Active,
            RentalAgreementStatus::Returned,
        ], true)) {
            throw new InvalidArgumentException('Running charts require an active or returned agreement context.');
        }
        if ($agreement->rateSnapshot === null) {
            throw new InvalidArgumentException('Agreement rate snapshot is missing.');
        }
        $to = $allocation->allocated_to ?? $agreement->expected_end_at;
        $outside = $dateOnly
            ? $at->lessThan($allocation->allocated_from->startOfDay())
                || $at->greaterThan($to->startOfDay())
            : $at->lessThan($allocation->allocated_from) || $at->greaterThan($to);
        if ($outside) {
            throw new InvalidArgumentException('Usage time must fall within every resolved vehicle allocation period.');
        }
    }
}
