<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Modules\VehicleRental\Http\Controllers\RentalLookupController;
use Modules\VehicleRental\Services\Validation\RentalAssignmentTimelineGuard;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RentalAssignmentSourceValidationContractTest extends TestCase
{
    public function test_linked_owner_supply_driver_is_not_treated_as_a_separate_booking(): void
    {
        $source = $this->source(RentalAssignmentTimelineGuard::class);

        self::assertStringContainsString('RentalAssignmentSide::CustomerUse', $source);
        self::assertStringContainsString('$data->sourceAssignmentId !== null', $source);
        self::assertStringContainsString('$query->whereKeyNot($data->sourceAssignmentId)', $source);
    }

    public function test_owner_supply_lookup_filters_to_vehicle_and_complete_period(): void
    {
        $source = $this->source(RentalLookupController::class);

        self::assertStringContainsString('RentalAssignmentSide::OwnerSupply, true', $source);
        self::assertStringContainsString("\$query->where('vehicle_id'", $source);
        self::assertStringContainsString("\$query->where('starts_at', '<='", $source);
        self::assertStringContainsString("\$scope->whereNull('ends_at')", $source);
        self::assertStringContainsString("->orWhere('ends_at', '>='", $source);
        self::assertStringContainsString("\$query->whereNull('ends_at')", $source);
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
