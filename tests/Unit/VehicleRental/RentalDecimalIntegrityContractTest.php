<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use PHPUnit\Framework\TestCase;

final class RentalDecimalIntegrityContractTest extends TestCase
{
    public function test_vehicle_rental_odometer_and_api_decimal_paths_never_use_binary_floats(): void
    {
        $root = dirname(__DIR__, 3);
        $resource = file_get_contents($root.'/app/Modules/VehicleRental/Http/Resources/RentalResource.php');
        $allocation = file_get_contents($root.'/app/Modules/VehicleRental/Services/RentalAllocationService.php');
        $custody = file_get_contents($root.'/app/Modules/VehicleRental/Services/RentalCustodyService.php');

        self::assertIsString($resource);
        self::assertIsString($allocation);
        self::assertIsString($custody);

        self::assertStringContainsString('app(DecimalMath::class)->normalize', $resource);
        self::assertStringNotContainsString('number_format((float)', $resource);
        self::assertStringNotContainsString('(float)', $allocation);
        self::assertStringNotContainsString('(float)', $custody);
    }
}
