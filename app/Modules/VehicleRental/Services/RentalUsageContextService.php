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
    public const MODE_LESSEE = 'lessee';
    public const MODE_LESSOR = 'lessor';
    public const MODE_LINKED = 'linked';

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

        return $this->attachResolved($usageLog, $resolved);
    }

    /**
     * @param  array{
     *   selected_agreement: RentalAgreement,
     *   selected_allocation: RentalAgreementVehicle,
     *   link: RentalAgreementVehicleLink|null,
     *   contexts: list<array{agreement: RentalAgreement, allocation: RentalAgreementVehicle}>
     * }  $resolved
     * @return Collection<int, RentalUsageContext>
     */
    public function attachResolved(RentalUsageLog $usageLog, array $resolved): Collection
    {
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
    public function resolveForMode(
        string $mode,
        RentalAgreement $selectedAgreement,
        RentalAgreementVehicle $selectedAllocation,
        string $usageDate,
        ?string $startTime = null,
        ?RentalAgreement $counterpartAgreement = null,
        ?RentalAgreementVehicle $counterpartAllocation = null,
    ): array {
        return match ($mode) {
            self::MODE_LESSEE => $this->resolveSingleMode(
                $selectedAgreement,
                $selectedAllocation,
                RentalAgreementDirection::Outbound,
                $usageDate,
                $startTime,
            ),
            self::MODE_LESSOR => $this->resolveSingleMode(
                $selectedAgreement,
                $selectedAllocation,
                RentalAgreementDirection::Inbound,
                $usageDate,
                $startTime,
            ),
            self::MODE_LINKED => $this->resolveLinkedMode(
                $selectedAgreement,
                $selectedAllocation,
                $counterpartAgreement,
                $counterpartAllocation,
                $usageDate,
                $startTime,
            ),
            default => throw new InvalidArgumentException('Unsupported running chart mode.'),
        };
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
                    ->where('effective_from', '<', $usageDay->addDay())
                    ->where('effective_to', '>', $usageDay),
                fn ($query) => $query
                    ->where('effective_from', '<=', $at)
                    ->where('effective_to', '>', $at),
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

    /**
     * @return array{
     *   selected_agreement: RentalAgreement,
     *   selected_allocation: RentalAgreementVehicle,
     *   link: null,
     *   contexts: list<array{agreement: RentalAgreement, allocation: RentalAgreementVehicle}>
     * }
     */
    private function resolveSingleMode(
        RentalAgreement $selectedAgreement,
        RentalAgreementVehicle $selectedAllocation,
        RentalAgreementDirection $requiredDirection,
        string $usageDate,
        ?string $startTime,
    ): array {
        if ($selectedAgreement->direction !== $requiredDirection) {
            throw new InvalidArgumentException(
                $requiredDirection === RentalAgreementDirection::Outbound
                    ? 'Lessee running charts require a customer agreement.'
                    : 'Lessor running charts require an owner or supplier agreement.',
            );
        }
        if ((int) $selectedAllocation->agreement_id !== (int) $selectedAgreement->getKey()) {
            throw new InvalidArgumentException('Selected vehicle allocation does not belong to the agreement.');
        }

        $at = $this->usageInstant($usageDate, $startTime);
        $selectedAgreement->loadMissing(['rateSnapshot', 'customer', 'supplier']);
        $this->assertEligible($selectedAgreement, $selectedAllocation, $at, $startTime === null);

        return [
            'selected_agreement' => $selectedAgreement,
            'selected_allocation' => $selectedAllocation,
            'link' => null,
            'contexts' => [[
                'agreement' => $selectedAgreement,
                'allocation' => $selectedAllocation,
            ]],
        ];
    }

    /**
     * @return array{
     *   selected_agreement: RentalAgreement,
     *   selected_allocation: RentalAgreementVehicle,
     *   link: RentalAgreementVehicleLink,
     *   contexts: list<array{agreement: RentalAgreement, allocation: RentalAgreementVehicle}>
     * }
     */
    private function resolveLinkedMode(
        RentalAgreement $selectedAgreement,
        RentalAgreementVehicle $selectedAllocation,
        ?RentalAgreement $counterpartAgreement,
        ?RentalAgreementVehicle $counterpartAllocation,
        string $usageDate,
        ?string $startTime,
    ): array {
        if ($counterpartAgreement === null || $counterpartAllocation === null) {
            throw new InvalidArgumentException('Linked running charts require both customer and owner/supplier agreements.');
        }
        if ($selectedAgreement->direction === $counterpartAgreement->direction) {
            throw new InvalidArgumentException('Linked running charts require one customer agreement and one owner/supplier agreement.');
        }

        $outboundAgreement = $selectedAgreement->direction === RentalAgreementDirection::Outbound
            ? $selectedAgreement
            : $counterpartAgreement;
        $outboundAllocation = $selectedAgreement->direction === RentalAgreementDirection::Outbound
            ? $selectedAllocation
            : $counterpartAllocation;
        $inboundAgreement = $selectedAgreement->direction === RentalAgreementDirection::Inbound
            ? $selectedAgreement
            : $counterpartAgreement;
        $inboundAllocation = $selectedAgreement->direction === RentalAgreementDirection::Inbound
            ? $selectedAllocation
            : $counterpartAllocation;

        foreach ([[$outboundAgreement, $outboundAllocation], [$inboundAgreement, $inboundAllocation]] as [$agreement, $allocation]) {
            if ((int) $allocation->agreement_id !== (int) $agreement->getKey()) {
                throw new InvalidArgumentException('Selected vehicle allocation does not belong to the agreement.');
            }
            if ((int) $agreement->tenant_id !== (int) $selectedAgreement->tenant_id
                || (int) $agreement->organization_unit_id !== (int) $selectedAgreement->organization_unit_id) {
                throw new InvalidArgumentException('Linked running charts must stay within one tenant and organization unit.');
            }
        }
        if ((int) $outboundAllocation->vehicle_id !== (int) $inboundAllocation->vehicle_id) {
            throw new InvalidArgumentException('Linked agreements must reference the same physical vehicle.');
        }

        $at = $this->usageInstant($usageDate, $startTime);
        $outboundAgreement->loadMissing(['rateSnapshot', 'customer']);
        $inboundAgreement->loadMissing(['rateSnapshot', 'supplier']);
        $this->assertEligible($outboundAgreement, $outboundAllocation, $at, $startTime === null);
        $this->assertEligible($inboundAgreement, $inboundAllocation, $at, $startTime === null);

        $link = RentalAgreementVehicleLink::query()
            ->forContext((int) $selectedAgreement->tenant_id, $selectedAgreement->organization_unit_id)
            ->where('vehicle_id', $outboundAllocation->vehicle_id)
            ->where('status', RentalAgreementVehicleLinkStatus::Approved->value)
            ->where('inbound_agreement_id', $inboundAgreement->getKey())
            ->where('inbound_agreement_vehicle_id', $inboundAllocation->getKey())
            ->where('outbound_agreement_id', $outboundAgreement->getKey())
            ->where('outbound_agreement_vehicle_id', $outboundAllocation->getKey())
            ->when(
                $startTime === null,
                fn ($query) => $query
                    ->where('effective_from', '<', $at->addDay())
                    ->where('effective_to', '>', $at),
                fn ($query) => $query
                    ->where('effective_from', '<=', $at)
                    ->where('effective_to', '>', $at),
            )
            ->when(DB::transactionLevel() > 0, fn ($query) => $query->lockForUpdate())
            ->get();
        if ($link->count() !== 1) {
            throw new InvalidArgumentException(
                'Linked running charts require exactly one approved allocation link for the selected agreements and usage time.',
            );
        }

        return [
            'selected_agreement' => $outboundAgreement,
            'selected_allocation' => $outboundAllocation,
            'link' => $link->sole(),
            'contexts' => [
                ['agreement' => $outboundAgreement, 'allocation' => $outboundAllocation],
                ['agreement' => $inboundAgreement, 'allocation' => $inboundAllocation],
            ],
        ];
    }

    private function usageInstant(string $usageDate, ?string $startTime): CarbonImmutable
    {
        $usageDay = CarbonImmutable::parse($usageDate)->startOfDay();

        return $startTime === null ? $usageDay : CarbonImmutable::parse($usageDate.' '.$startTime);
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
                || ! $at->lessThan($to)
            : $at->lessThan($allocation->allocated_from) || ! $at->lessThan($to);
        if ($outside) {
            throw new InvalidArgumentException('Usage time must fall within every resolved vehicle allocation period.');
        }
    }
}
