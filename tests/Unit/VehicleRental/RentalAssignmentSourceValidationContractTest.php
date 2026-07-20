<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Modules\VehicleRental\Http\Controllers\RentalLookupController;
use Modules\VehicleRental\Services\RentalAssignmentService;
use Modules\VehicleRental\Services\RentalCustodyService;
use Modules\VehicleRental\Services\RentalReplacementService;
use Modules\VehicleRental\Services\Validation\RentalAssignmentSourceGuard;
use Modules\VehicleRental\Services\Validation\RentalAssignmentTimelineGuard;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RentalAssignmentSourceValidationContractTest extends TestCase
{
    public function test_linked_owner_supply_and_customer_use_share_driver_without_becoming_separate_bookings(): void
    {
        $source = $this->source(RentalAssignmentTimelineGuard::class);

        self::assertStringContainsString('RentalAssignmentSide::CustomerUse', $source);
        self::assertStringContainsString('$data->sourceAssignmentId !== null', $source);
        self::assertStringContainsString('$query->whereKeyNot($data->sourceAssignmentId)', $source);
        self::assertStringContainsString('RentalAssignmentSide::OwnerSupply', $source);
        self::assertStringContainsString('$ignoreAssignmentId !== null', $source);
        self::assertStringContainsString('$scope->where(\'side\', \'!=\', RentalAssignmentSide::CustomerUse->value)', $source);
        self::assertStringContainsString('->orWhereNull(\'source_assignment_id\')', $source);
        self::assertStringContainsString('->orWhere(\'source_assignment_id\', \'!=\', $ignoreAssignmentId)', $source);
        self::assertStringContainsString('The selected driver already has an overlapping rental assignment.', $source);
    }

    public function test_owner_supply_lookup_uses_planning_statuses_and_calendar_date_coverage(): void
    {
        $source = $this->source(RentalLookupController::class);

        self::assertStringContainsString('RentalAssignmentStatus::Planned->value', $source);
        self::assertStringContainsString('RentalAssignmentStatus::Active->value', $source);
        self::assertStringContainsString('$query->where(\'vehicle_id\'', $source);
        self::assertStringContainsString('$query->whereDate(\'starts_at\', \'<=\'', $source);
        self::assertStringContainsString('$scope->whereNull(\'ends_at\')', $source);
        self::assertStringContainsString('->orWhereDate(\'ends_at\', \'>=\'', $source);
        self::assertStringContainsString('$query->whereNull(\'ends_at\')', $source);
    }

    public function test_planning_and_operational_source_eligibility_are_separate(): void
    {
        $guard = $this->source(RentalAssignmentSourceGuard::class);
        $assignmentService = $this->source(RentalAssignmentService::class);
        $custodyService = $this->source(RentalCustodyService::class);
        $replacementService = $this->source(RentalReplacementService::class);

        self::assertStringContainsString('sourceAssignmentForPlanning', $guard);
        self::assertStringContainsString('periodContainsPlanningDates', $guard);
        self::assertStringContainsString('sourceAssignmentForOperation', $guard);
        self::assertStringContainsString('periodContainsOperationalPeriod', $guard);
        self::assertStringContainsString('sourceAssignmentForPlanning', $assignmentService);
        self::assertStringContainsString('sourceAssignmentForOperation', $custodyService);
        self::assertStringContainsString('sourceAssignmentForOperation', $replacementService);
    }

    private function source(string $class): string
    {
        $file = (new ReflectionClass($class))->getFileName();
        self::assertIsString($file);

        $source = file_get_contents($file);
        self::assertIsString($source);

        return $source;
    }
}
