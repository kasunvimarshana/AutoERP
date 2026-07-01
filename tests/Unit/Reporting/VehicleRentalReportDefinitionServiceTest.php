<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use Modules\Core\Services\DecimalMath;
use Modules\Reporting\Services\VehicleRentalReportDefinitionService;
use Modules\VehicleRental\Models\RentalUsageFact;
use Modules\VehicleRental\Models\RentalUsageLog;
use PHPUnit\Framework\TestCase;

final class VehicleRentalReportDefinitionServiceTest extends TestCase
{
    public function test_vehicle_rental_reports_separate_physical_and_commercial_facts(): void
    {
        if (! extension_loaded('bcmath')) {
            self::markTestSkipped('BCMath is required by DecimalMath.');
        }

        $definitions = collect(
            (new VehicleRentalReportDefinitionService(new DecimalMath()))->definitions(),
        )->keyBy(fn ($definition) => $definition->key);

        self::assertSame(
            RentalUsageLog::class,
            $definitions->get('vehicle-rental.running-chart')->model,
        );
        self::assertNotNull(
            $definitions->get('vehicle-rental.running-chart')->column('net_operational_distance_km'),
        );
        self::assertNull(
            $definitions->get('vehicle-rental.running-chart')->column('chargeable_distance_km'),
        );

        self::assertSame(
            RentalUsageFact::class,
            $definitions->get('vehicle-rental.customer-running-chart')->model,
        );
        self::assertSame(
            'revenue',
            $definitions->get('vehicle-rental.customer-running-chart')->constraints['financial_side'],
        );
        self::assertSame(
            'cost',
            $definitions->get('vehicle-rental.owner-running-chart')->constraints['financial_side'],
        );
    }
}
