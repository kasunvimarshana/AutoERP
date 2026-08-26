<?php

declare(strict_types=1);

namespace Tests\Unit\Vehicle;

use Illuminate\Support\Facades\Schema;
use Modules\Vehicle\DTOs\CreateVehicleData;
use Tests\TestCase;

final class VehicleOptionalOdometerContractTest extends TestCase
{
    public function test_missing_vehicle_odometer_is_not_fabricated_as_zero(): void
    {
        self::assertNull((new CreateVehicleData(tenantId: 1))->odometerReading);

        $requestMapper = file_get_contents(base_path('app/Modules/Vehicle/Http/Requests/Concerns/MapsVehicleData.php'));
        $creationService = file_get_contents(base_path('app/Modules/Vehicle/Services/VehicleCreationService.php'));
        $validationService = file_get_contents(base_path('app/Modules/Vehicle/Validators/VehicleValidationService.php'));
        $summaryResource = file_get_contents(base_path('app/Modules/Vehicle/Http/Resources/VehicleSummaryResource.php'));
        $baselineMigration = file_get_contents(base_path('app/Modules/Vehicle/Database/Migrations/2026_06_12_120005_create_vehicles_table.php'));
        $upgradeMigration = file_get_contents(base_path('database/migrations/2026_07_22_000004_allow_vehicles_without_odometer.php'));

        self::assertIsString($requestMapper);
        self::assertIsString($creationService);
        self::assertIsString($validationService);
        self::assertIsString($summaryResource);
        self::assertIsString($baselineMigration);
        self::assertIsString($upgradeMigration);
        self::assertStringContainsString("odometerReading: \$this->nullableString(\$vehicle, 'odometer_reading')", $requestMapper);
        self::assertStringContainsString("'odometer_reading' => \$data->odometerReading === null", $creationService);
        self::assertStringContainsString('if ($data->odometerReading !== null)', $validationService);
        self::assertStringContainsString("'odometer_reading' => \$this->odometer_reading === null ? null", $summaryResource);
        self::assertStringContainsString("\$table->decimal('odometer_reading', 20, 6)->nullable();", $baselineMigration);
        self::assertStringContainsString("\$table->decimal('odometer_reading', 20, 6)->nullable()->default(null)->change();", $upgradeMigration);
        self::assertStringNotContainsString("odometer_reading'] ?? '0.000000'", $requestMapper);

        $odometerColumn = collect(Schema::getColumns('vehicles'))->firstWhere('name', 'odometer_reading');
        self::assertIsArray($odometerColumn);
        self::assertTrue((bool) ($odometerColumn['nullable'] ?? false));
    }
}
