<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services\Validation;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\DTOs\RentalRateLineData;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalBillingBasis;
use Modules\VehicleRental\Enums\RentalRateCode;
use Modules\VehicleRental\Enums\RentalRateUnit;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalRateLine;
use Modules\VehicleRental\Models\RentalRateVersion;

final class RentalRatePolicy
{
    public function __construct(private readonly DecimalMath $math) {}

    /** @param list<RentalRateLineData> $rates */
    public function validate(
        RentalAgreementKind $kind,
        RentalBillingBasis $billingBasis,
        array $rates,
    ): void {
        if ($rates === []) {
            throw new InvalidArgumentException('A rental agreement requires at least one rate.');
        }

        $codes = [];
        $hasBaseRate = false;
        $hasAcModeRate = false;
        foreach ($rates as $rate) {
            if (! $rate instanceof RentalRateLineData) {
                throw new InvalidArgumentException('Rental agreement rates must be RentalRateLineData values.');
            }
            if (isset($codes[$rate->code->value])) {
                throw new InvalidArgumentException("Rental rate [{$rate->code->value}] cannot be duplicated.");
            }
            $codes[$rate->code->value] = true;

            if ($this->math->isNegative($rate->rate)) {
                throw new InvalidArgumentException('Rental agreement rates cannot be negative.');
            }
            if (! in_array($rate->unit, $rate->code->allowedUnits(), true)) {
                throw new InvalidArgumentException(sprintf(
                    'Rate [%s] does not support unit [%s].',
                    $rate->code->value,
                    $rate->unit->value,
                ));
            }
            if ($billingBasis === RentalBillingBasis::Monthly && $rate->code->isAcModeRate()) {
                throw new InvalidArgumentException(
                    'AC-mode rates are supported only for daily agreements until a monthly AC pricing policy is configured.',
                );
            }
            if ($kind === RentalAgreementKind::Owner && $rate->code->isAcModeRate()) {
                throw new InvalidArgumentException('Owner agreements use one standard owner-payable base rate.');
            }

            $hasBaseRate = $hasBaseRate || $rate->code === RentalRateCode::BaseRental;
            $hasAcModeRate = $hasAcModeRate || $rate->code->isAcModeRate();
        }

        if ($hasBaseRate && $hasAcModeRate) {
            throw new InvalidArgumentException('Choose either a standard base rental rate or AC-mode rental rates, not both.');
        }
    }

    public function assertRequiredBaseRate(RentalAgreement $agreement, RentalRateVersion $version): void
    {
        $base = $version->lines->first(
            fn (RentalRateLine $line): bool => $line->rate_code === RentalRateCode::BaseRental,
        );
        $acModeRates = $version->lines->filter(
            fn (RentalRateLine $line): bool => $line->rate_code->isAcModeRate(),
        );

        if ($agreement->kind === RentalAgreementKind::Customer
            && $agreement->billing_basis === RentalBillingBasis::Daily
            && $acModeRates->isNotEmpty()) {
            if ($base instanceof RentalRateLine) {
                throw new InvalidArgumentException('Daily customer agreements cannot mix a base rental rate with AC-mode rates.');
            }
            foreach ($acModeRates as $rate) {
                if ($rate->unit !== RentalRateUnit::Day) {
                    throw new InvalidArgumentException('Daily customer AC-mode rates must use the day unit.');
                }
            }

            return;
        }

        if (! $base instanceof RentalRateLine) {
            throw new InvalidArgumentException('A base rental rate is required before activation.');
        }
        $requiredUnit = $agreement->billing_basis === RentalBillingBasis::Daily
            ? RentalRateUnit::Day
            : RentalRateUnit::Month;
        if ($base->unit !== $requiredUnit) {
            throw new InvalidArgumentException("Base rental unit must be [{$requiredUnit->value}] for this agreement billing basis.");
        }
    }
}
