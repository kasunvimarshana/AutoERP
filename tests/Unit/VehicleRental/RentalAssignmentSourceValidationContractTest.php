<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Modules\VehicleRental\Http\Controllers\RentalLookupController;
use Modules\VehicleRental\Http\Requests\RentalDateTimeRules;
use Modules\VehicleRental\Http\Requests\RentalRunningChartMutationRequest;
use Modules\VehicleRental\Http\Requests\ReplaceRentalAssignmentRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalAssignmentRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalCustodyRequest;
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
    }

    public function test_unrelated_driver_overlap_identifies_the_conflicting_assignment(): void
    {
        $source = $this->source(RentalAssignmentTimelineGuard::class);

        self::assertStringContainsString("->with('agreement:id,agreement_number')", $source);
        self::assertStringContainsString('driverConflictMessage', $source);
        self::assertStringContainsString('The selected driver already has an overlapping rental assignment:', $source);
        self::assertStringContainsString('$conflict->side->value', $source);
        self::assertStringContainsString('$conflict->status->value', $source);
        self::assertStringContainsString('$conflict->starts_at->toDateTimeString()', $source);
    }

    public function test_owner_supply_lookup_uses_planning_statuses_and_exact_timestamp_coverage(): void
    {
        $source = $this->source(RentalLookupController::class);

        self::assertStringContainsString('RentalAssignmentStatus::Planned->value', $source);
        self::assertStringContainsString('RentalAssignmentStatus::Active->value', $source);
        self::assertStringContainsString('$query->where(\'vehicle_id\'', $source);
        self::assertStringContainsString('$query->where(\'starts_at\', \'<=\', $startsAt)', $source);
        self::assertStringContainsString('->orWhere(\'ends_at\', \'>=\', $endsAt)', $source);
        self::assertStringContainsString('$query->whereNull(\'ends_at\')', $source);
        self::assertStringContainsString('->utc()', $source);
        self::assertStringNotContainsString('whereDate(\'starts_at\'', $source);
        self::assertStringNotContainsString('orWhereDate(\'ends_at\'', $source);
    }

    public function test_owner_vehicle_lookup_matches_the_agreement_supplier_and_complete_ownership_period(): void
    {
        $source = $this->source(RentalLookupController::class);

        self::assertStringContainsString('function ownerAgreementVehicles', $source);
        self::assertStringContainsString('RentalAgreementKind::Owner', $source);
        self::assertStringContainsString("->whereHas('ownerships'", $source);
        self::assertStringContainsString('VehicleOwnerType::Supplier->value', $source);
        self::assertStringContainsString('->where(\'owner_id\', $supplierId)', $source);
        self::assertStringContainsString('->where(\'started_at\', \'<=\', $startsAt)', $source);
        self::assertStringContainsString('->orWhere(\'ended_at\', \'>=\', $endsAt)', $source);
        self::assertStringNotContainsString('VehicleOwnership::create', $source);
        self::assertStringNotContainsString('VehicleOwnership::query()->create', $source);
    }

    public function test_planning_and_operational_source_eligibility_share_one_exact_period_boundary(): void
    {
        $guard = $this->source(RentalAssignmentSourceGuard::class);
        $assignmentService = $this->source(RentalAssignmentService::class);
        $custodyService = $this->source(RentalCustodyService::class);
        $replacementService = $this->source(RentalReplacementService::class);

        self::assertStringContainsString('sourceAssignmentForPlanning', $guard);
        self::assertStringContainsString('sourceAssignmentForOperation', $guard);
        self::assertSame(3, substr_count($guard, 'periodContainsCompletePeriod'));
        self::assertStringNotContainsString('periodContainsPlanningDates', $guard);
        self::assertStringNotContainsString('periodContainsOperationalPeriod', $guard);
        self::assertStringContainsString('Owner-supply assignment must cover the complete customer-use assignment period.', $guard);
        self::assertStringContainsString('Active owner-supply assignment must cover the complete customer-use operational period.', $guard);
        self::assertStringContainsString('sourceAssignmentForPlanning', $assignmentService);
        self::assertStringContainsString('sourceAssignmentForOperation', $custodyService);
        self::assertStringContainsString('sourceAssignmentForOperation', $replacementService);
    }

    public function test_rental_datetime_mutations_require_explicit_offsets_and_normalize_instants_to_utc(): void
    {
        $rule = $this->source(RentalDateTimeRules::class);
        $assignmentRequest = $this->source(StoreRentalAssignmentRequest::class);
        $custodyRequest = $this->source(StoreRentalCustodyRequest::class);
        $replacementRequest = $this->source(ReplaceRentalAssignmentRequest::class);
        $runningChartRequest = $this->source(RentalRunningChartMutationRequest::class);
        $timeline = $this->source(RentalAssignmentTimelineGuard::class);

        self::assertStringContainsString('EXPLICIT_TIMEZONE_PATTERN', $rule);
        self::assertStringContainsString('RentalDateTimeRules::required()', $assignmentRequest);
        self::assertStringContainsString('RentalDateTimeRules::nullable()', $assignmentRequest);
        self::assertStringContainsString('RentalDateTimeRules::required()', $custodyRequest);
        self::assertStringContainsString('RentalDateTimeRules::required()', $replacementRequest);
        self::assertSame(2, substr_count($runningChartRequest, 'RentalDateTimeRules::required()'));
        self::assertStringContainsString('->utc()->seconds(0)', $timeline);
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
