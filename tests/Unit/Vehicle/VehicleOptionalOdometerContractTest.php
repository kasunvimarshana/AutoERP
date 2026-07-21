<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicle;

use Modules\Vehicle\DTOs\CreateVehicleData;
use Tests\TestCase;

final class VehicleOptionalOdometerContractTest extends TestCase
{
    public function test_missing_vehicle_odometer_is_not_fabricated_as_zero(): void
    {
        self::assertNull((new CreateVehicleData(tenantId: 1))->odometerReading);

        $requestMapper = file_get_contents(base_path('app/Modules/Vehicle/Http/Requests/Concerns/MapsVehicleData.php'));
        $creationService = file_get_contents(base_path('app/Modules/Vehicle/Services/VehicleCreationService.php'));
        $summaryResource = file_get_contents(base_path('app/Modules/Vehicle/Http/Resources/VehicleSummaryResource.php'));

        self::assertIsString($requestMapper);
        self::assertIsString($creationService);
        self::assertIsString($summaryResource);
        self::assertStringContainsString("odometerReading: \$this->nullableString(\$vehicle, 'odometer_reading')", $requestMapper);
        self::assertStringContainsString("'odometer_reading' => \$data->odometerReading === null", $creationService);
        self::assertStringContainsString("'odometer_reading' => \$this->odometer_reading === null ? null", $summaryResource);
        self::assertStringNotContainsString("odometer_reading'] ?? '0.000000'", $requestMapper);
    }
}
