<?php

declare(strict_types=1);

namespace Modules\VehicleService\Tests;

use Tests\TestCase;

final class VehicleServiceInspectionWorkforceContractTest extends TestCase
{
    public function test_inspection_workforce_rule_is_enforced_by_the_backend_owner_service(): void
    {
        $source = file_get_contents(
            base_path('app/Modules/VehicleService/Services/VehicleServiceStatusService.php'),
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            'if ($status === VehicleServiceJobStatus::Inspected)',
            $source,
        );
        self::assertStringContainsString(
            '$this->assertWorkforceReadyForInspection($job);',
            $source,
        );
        self::assertStringContainsString(
            "->whereHas('employeeAssignments'",
            $source,
        );
        self::assertStringContainsString(
            'throw new InvalidArgumentException(self::WORKFORCE_REQUIRED_MESSAGE);',
            $source,
        );
    }
}
