<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Carbon\CarbonImmutable;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAgreementRateVersion;
use Modules\VehicleRental\Services\RentalCalculationService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

final class RentalCalculationTaxTreatmentTest extends TestCase
{
    #[Test]
    public function non_taxable_positive_lines_are_not_sent_to_the_tax_engine(): void
    {
        $service = app(RentalCalculationService::class);
        $reflection = new ReflectionClass($service);
        $taxableKey = $reflection->getConstant('DRAFT_TAXABLE_KEY');
        self::assertIsString($taxableKey);

        $agreement = new RentalAgreement();
        $agreement->forceFill([
            'tenant_id' => 1,
            'organization_unit_id' => null,
            'customer_id' => 1,
            'supplier_id' => null,
        ]);
        $rate = new RentalAgreementRateVersion();
        $rate->forceFill([
            'tax_group_id' => null,
            'withholding_tax_group_id' => null,
        ]);
        $lines = [[
            'net_amount' => '100.000000',
            'tax_group_id' => null,
            'tax_amount' => '0.000000',
            'withholding_amount' => '0.000000',
            'total_amount' => '100.000000',
            $taxableKey => false,
        ]];

        $method = new ReflectionMethod($service, 'applyTax');
        $result = $method->invoke(
            $service,
            $agreement,
            RentalFinancialSide::Revenue,
            $rate,
            CarbonImmutable::parse('2026-07-17'),
            $lines,
        );

        self::assertSame($lines, $result);
    }
}
