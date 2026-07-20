<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Modules\VehicleRental\Services\RentalAssignmentService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RentalAssignmentCrudLifecycleContractTest extends TestCase
{
    public function test_planned_update_reuses_assignment_validation_with_current_record_excluded(): void
    {
        $source = $this->source();

        self::assertStringContainsString('public function update(', $source);
        self::assertStringContainsString('$this->assertUnusedPlanned($assignment, \'edited\')', $source);
        self::assertStringContainsString('A rental assignment cannot use itself as an owner-supply source.', $source);
        self::assertStringContainsString('vehiclesAlreadyLocked: true', $source);
        self::assertStringContainsString('$this->timeline->assertNoVehicleOverlap(', $source);
        self::assertStringContainsString('$this->timeline->assertDriverAvailable(', $source);
        self::assertStringContainsString('$ignoreAssignmentId', $source);
        self::assertStringContainsString('$this->sources->sourceAssignmentForPlanning(', $source);
        self::assertStringContainsString('$this->sources->assertOwnershipSource(', $source);
    }

    public function test_delete_is_version_checked_and_limited_to_unused_planned_records(): void
    {
        $source = $this->source();

        self::assertStringContainsString('public function deletePlanned(', $source);
        self::assertStringContainsString('$this->timeline->assertExpectedVersion($assignment, $expectedVersion)', $source);
        self::assertStringContainsString('$this->assertUnusedPlanned($assignment, \'deleted\')', $source);
        self::assertStringContainsString('Only planned rental assignments can be {$action}.', $source);
        self::assertStringContainsString('custodyEvents()->lockForUpdate()', $source);
        self::assertStringContainsString('runningCharts()->lockForUpdate()', $source);
        self::assertStringContainsString('->where(\'source_assignment_id\', $assignment->getKey())', $source);
        self::assertStringContainsString('->orWhere(\'replaces_assignment_id\', $assignment->getKey())', $source);
        self::assertStringContainsString('$assignment->delete()', $source);
    }

    private function source(): string
    {
        $file = (new ReflectionClass(RentalAssignmentService::class))->getFileName();
        self::assertIsString($file);

        $source = file_get_contents($file);
        self::assertIsString($source);

        return $source;
    }
}
