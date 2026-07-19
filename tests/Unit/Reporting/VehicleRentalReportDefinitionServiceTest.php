<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use Modules\Core\Services\DecimalMath;
use Modules\Reporting\DTOs\ReportDefinition;
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

        $definitions = [];
        foreach ((new VehicleRentalReportDefinitionService(new DecimalMath()))->definitions() as $definition) {
            $definitions[$definition->key] = $definition;
        }

        $physical = $this->definition($definitions, 'vehicle-rental.running-chart');
        self::assertSame(RentalUsageLog::class, $physical->model);
        self::assertNotNull($physical->column('net_operational_distance_km'));
        self::assertNull($physical->column('chargeable_distance_km'));

        $customer = $this->definition($definitions, 'vehicle-rental.customer-running-chart');
        self::assertSame(RentalUsageFact::class, $customer->model);
        self::assertSame('revenue', $customer->constraints['financial_side']);

        $owner = $this->definition($definitions, 'vehicle-rental.owner-running-chart');
        self::assertSame('cost', $owner->constraints['financial_side']);
    }

    /**
     * @param array<string, ReportDefinition> $definitions
     */
    private function definition(array $definitions, string $key): ReportDefinition
    {
        self::assertArrayHasKey($key, $definitions);

        return $definitions[$key];
    }
}
