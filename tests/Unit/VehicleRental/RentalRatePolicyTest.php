<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\DTOs\RentalRateLineData;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalBillingBasis;
use Modules\VehicleRental\Enums\RentalRateCode;
use Modules\VehicleRental\Enums\RentalRateUnit;
use Modules\VehicleRental\Services\Validation\RentalRatePolicy;
use PHPUnit\Framework\TestCase;

final class RentalRatePolicyTest extends TestCase
{
    public function test_daily_customer_agreement_accepts_explicit_ac_mode_rates_without_standard_base(): void
    {
        $this->policy()->validate(
            RentalAgreementKind::Customer,
            RentalBillingBasis::Daily,
            [
                $this->rate(RentalRateCode::NonAc, RentalRateUnit::Day, '10000'),
                $this->rate(RentalRateCode::FrontAc, RentalRateUnit::Day, '12000'),
                $this->rate(RentalRateCode::DualAc, RentalRateUnit::Day, '14000'),
            ],
        );

        self::assertTrue(true);
    }

    public function test_standard_base_and_ac_mode_rates_cannot_be_mixed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Choose either a standard base rental rate or AC-mode rental rates, not both.');

        $this->policy()->validate(
            RentalAgreementKind::Customer,
            RentalBillingBasis::Daily,
            [
                $this->rate(RentalRateCode::BaseRental, RentalRateUnit::Day, '10000'),
                $this->rate(RentalRateCode::NonAc, RentalRateUnit::Day, '9000'),
            ],
        );
    }

    public function test_monthly_and_owner_agreements_reject_ac_mode_rates(): void
    {
        foreach ([
            [RentalAgreementKind::Customer, RentalBillingBasis::Monthly],
            [RentalAgreementKind::Owner, RentalBillingBasis::Daily],
        ] as [$kind, $basis]) {
            try {
                $this->policy()->validate(
                    $kind,
                    $basis,
                    [$this->rate(RentalRateCode::NonAc, RentalRateUnit::Day, '9000')],
                );
                self::fail('Expected AC-mode rate validation to fail.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    private function policy(): RentalRatePolicy
    {
        return new RentalRatePolicy(new DecimalMath());
    }

    private function rate(RentalRateCode $code, RentalRateUnit $unit, string $amount): RentalRateLineData
    {
        return new RentalRateLineData($code, $unit, $amount, true);
    }
}
