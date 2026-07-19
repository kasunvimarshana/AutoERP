<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Tests\TestCase;

final class RentalSourceConsumptionMigrationTest extends TestCase
{
    public function test_running_chart_and_side_consumption_use_portable_active_markers(): void
    {
        $module = dirname(__DIR__, 3).'/app/Modules/VehicleRental/Database/Migrations/';
        $charts = file_get_contents($module.'2026_07_19_300006_create_vehicle_rental_running_charts_table.php');
        $sources = file_get_contents($module.'2026_07_19_300009_create_vehicle_rental_calculation_sources_table.php');

        self::assertIsString($charts);
        self::assertIsString($sources);
        self::assertStringContainsString("['assignment_id', 'operational_date', 'active_marker']", $charts);
        self::assertStringContainsString("['running_chart_id', 'side', 'active_marker']", $sources);
        self::assertStringContainsString("['calculation_id', 'running_chart_id']", $sources);
    }
}
