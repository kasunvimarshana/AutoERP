<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RentalUsageCreationLockOrderContractTest extends TestCase
{
    #[Test]
    public function running_chart_creation_uses_one_deterministic_resource_lock_order(): void
    {
        $service = file_get_contents(
            base_path('app/Modules/VehicleRental/Services/RentalUsageCreationService.php'),
        );
        $controller = file_get_contents(
            base_path('app/Modules/VehicleRental/Http/Controllers/RentalUsageController.php'),
        );

        self::assertIsString($service);
        self::assertIsString($controller);

        $vehicleTimeline = strpos($service, '$this->lockVehicleTimeline(');
        $currentAllocation = strpos($service, '$current = RentalVehicleAllocation::query()');
        $sourceValidation = strpos($service, '$this->assertSourceUsesLockedTimeline($current);');
        $driverTimeline = strpos($service, '$this->lockDriverTimeline($current, $data);');
        $usageEngine = strpos($service, 'return $this->usage->create($current, $data, $userId);');

        self::assertNotFalse($vehicleTimeline);
        self::assertNotFalse($currentAllocation);
        self::assertNotFalse($sourceValidation);
        self::assertNotFalse($driverTimeline);
        self::assertNotFalse($usageEngine);
        self::assertLessThan($currentAllocation, $vehicleTimeline);
        self::assertLessThan($sourceValidation, $currentAllocation);
        self::assertLessThan($driverTimeline, $sourceValidation);
        self::assertLessThan($usageEngine, $driverTimeline);

        self::assertStringContainsString("->orderBy('id')\n            ->lockForUpdate()", $service);
        self::assertStringContainsString('RentalUsageCreationService $creation', $controller);
        self::assertStringContainsString('$creation->create(', $controller);
        self::assertStringNotContainsString('$service->create(', $controller);
    }

    #[Test]
    public function source_and_driver_identity_changes_fail_closed_before_usage_persistence(): void
    {
        $service = file_get_contents(
            base_path('app/Modules/VehicleRental/Services/RentalUsageCreationService.php'),
        );

        self::assertIsString($service);
        self::assertStringContainsString('assertTimelineIdentity($snapshot, $current)', $service);
        self::assertStringContainsString('The owner supply allocation must use the same vehicle', $service);
        self::assertStringContainsString('The driver assignment changed while the running chart was being prepared', $service);
        self::assertStringContainsString('DB::transaction(', $service);
    }
}
