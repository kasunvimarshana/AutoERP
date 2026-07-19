<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use PHPUnit\Framework\TestCase;

final class RentalOdometerContinuityContractTest extends TestCase
{
    public function test_adjacent_running_chart_odometer_values_require_exact_match_or_variance_reason(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 3).'/app/Modules/VehicleRental/Services/RentalUsageService.php',
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            'Start odometer does not match the previous recorded finish. Provide a variance reason.',
            $source,
        );
        self::assertStringContainsString(
            'Finish odometer does not match the next recorded start. Provide a variance reason.',
            $source,
        );
        self::assertGreaterThanOrEqual(2, substr_count($source, ') !== 0'));
        self::assertStringNotContainsString(
            'Start odometer is below the previous recorded finish.',
            $source,
        );
        self::assertStringNotContainsString(
            'Finish odometer exceeds the next recorded start.',
            $source,
        );
    }
}
