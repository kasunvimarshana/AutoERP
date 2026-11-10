<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Enums\VehicleOwnershipType;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\TestCase;

final class VehicleUseReplacementValidationTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-09-07T12:00:00+00:00'));
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
        });
    }

    public function test_missing_replacement_start_is_rejected_before_existing_custody_changes(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $service = app(VehicleUseService::class);
            $use = $service->plan($context, $customer->id, $customer->row_version, $input);
            $use = $service->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => $input['starts_at'],
                'odometer' => '100',
                'reason' => 'Collected',
            ]);

            $replacement = $input;
            unset($replacement['starts_at']);
            $replacement['reason'] = 'Exchange vehicle';

            try {
                $service->replace($context, $use->id, $use->row_version, $replacement);
                self::fail('Replacement without starts_at was accepted.');
            } catch (ValidationException $error) {
                self::assertArrayHasKey('starts_at', $error->errors());
            }

            self::assertSame(VehicleUseStatus::InCustody, $use->refresh()->status);
            self::assertSame(2, $use->history()->count());
            $this->assertDatabaseCount('vehicle_rental_uses', 1);
        });
    }

    public function test_invalid_replacement_odometer_is_rejected_before_existing_custody_changes(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $service = app(VehicleUseService::class);
            $use = $service->plan($context, $customer->id, $customer->row_version, $input);
            $use = $service->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => $input['starts_at'],
                'odometer' => '100',
                'reason' => 'Collected',
            ]);

            $replacement = $input + [
                'reason' => 'Exchange vehicle',
                'return_odometer' => 'not-a-number',
            ];

            try {
                $service->replace($context, $use->id, $use->row_version, $replacement);
                self::fail('Replacement with invalid odometer was accepted.');
            } catch (ValidationException $error) {
                self::assertArrayHasKey('return_odometer', $error->errors());
            }

            self::assertSame(VehicleUseStatus::InCustody, $use->refresh()->status);
            self::assertSame(2, $use->history()->count());
            $this->assertDatabaseCount('vehicle_rental_uses', 1);
        });
    }

    public function test_future_replacement_time_is_rejected_before_existing_custody_changes(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $service = app(VehicleUseService::class);
            $use = $service->plan($context, $customer->id, $customer->row_version, $input);
            $use = $service->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => $input['starts_at'],
                'odometer' => '100',
                'reason' => 'Collected',
            ]);

            $newVehicleId = DB::table('vehicles')->insertGetId([
                'tenant_id' => $context->tenantId,
                'vehicle_number' => 'REPLACEMENT-FUTURE',
                'registration_number' => 'REPLACEMENT-FUTURE',
                'status' => VehicleStatus::Active->value,
            ]);
            DB::table('vehicle_ownerships')->insert([
                'tenant_id' => $context->tenantId,
                'vehicle_id' => $newVehicleId,
                'owner_type' => VehicleOwnerType::Company->value,
                'owner_key' => 'company',
                'owner_code_snapshot' => 'COMPANY',
                'owner_name_snapshot' => 'Company',
                'ownership_type' => VehicleOwnershipType::CompanyOwned->value,
                'started_at' => '2026-09-01 00:00:00',
            ]);

            // Travel time is 2026-09-07T12:00:00+00:00 (17:30:00+05:30). A replacement at 18:00 is in the future.
            $replacement = array_merge($input, [
                'vehicle_id' => $newVehicleId,
                'owner_agreement_id' => null,
                'reason' => 'Exchange vehicle',
                'starts_at' => '2026-09-07T18:00:00+05:30',
            ]);

            try {
                $service->replace($context, $use->id, $use->row_version, $replacement);
                self::fail('Future replacement time was accepted.');
            } catch (ValidationException $error) {
                self::assertArrayHasKey('starts_at', $error->errors());
                self::assertSame('Replacement time cannot be in the future.', $error->errors()['starts_at'][0]);
            }

            self::assertSame(VehicleUseStatus::InCustody, $use->refresh()->status);
            self::assertSame(2, $use->history()->count());
            $this->assertDatabaseCount('vehicle_rental_uses', 1);
        });
    }

    public function test_replacement_time_at_or_before_previous_handover_is_rejected_before_custody_changes(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $service = app(VehicleUseService::class);
            $use = $service->plan($context, $customer->id, $customer->row_version, $input);
            $use = $service->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => $input['starts_at'],
                'odometer' => '100',
                'reason' => 'Collected',
            ]);

            $newVehicleId = DB::table('vehicles')->insertGetId([
                'tenant_id' => $context->tenantId,
                'vehicle_number' => 'REPLACEMENT-PAST',
                'registration_number' => 'REPLACEMENT-PAST',
                'status' => VehicleStatus::Active->value,
            ]);
            DB::table('vehicle_ownerships')->insert([
                'tenant_id' => $context->tenantId,
                'vehicle_id' => $newVehicleId,
                'owner_type' => VehicleOwnerType::Company->value,
                'owner_key' => 'company',
                'owner_code_snapshot' => 'COMPANY',
                'owner_name_snapshot' => 'Company',
                'ownership_type' => VehicleOwnershipType::CompanyOwned->value,
                'started_at' => '2026-09-01 00:00:00',
            ]);

            // Replacement at the exact same timestamp as previous handover
            $sameTimeReplacement = array_merge($input, [
                'vehicle_id' => $newVehicleId,
                'owner_agreement_id' => null,
                'reason' => 'Exchange vehicle',
                'starts_at' => $input['starts_at'],
            ]);

            try {
                $service->replace($context, $use->id, $use->row_version, $sameTimeReplacement);
                self::fail('Replacement time equal to handover was accepted.');
            } catch (ValidationException $error) {
                self::assertArrayHasKey('starts_at', $error->errors());
                self::assertSame("Replacement time must follow the previous vehicle's handover time.", $error->errors()['starts_at'][0]);
            }

            // Replacement before previous handover
            $earlierReplacement = array_merge($input, [
                'vehicle_id' => $newVehicleId,
                'owner_agreement_id' => null,
                'reason' => 'Exchange vehicle',
                'starts_at' => '2026-09-07T08:00:00+05:30',
            ]);

            try {
                $service->replace($context, $use->id, $use->row_version, $earlierReplacement);
                self::fail('Replacement time before handover was accepted.');
            } catch (ValidationException $error) {
                self::assertArrayHasKey('starts_at', $error->errors());
                self::assertSame("Replacement time must follow the previous vehicle's handover time.", $error->errors()['starts_at'][0]);
            }

            self::assertSame(VehicleUseStatus::InCustody, $use->refresh()->status);
            self::assertSame(2, $use->history()->count());
            $this->assertDatabaseCount('vehicle_rental_uses', 1);
        });
    }

    public function test_valid_replacement_between_handover_and_now_succeeds_end_to_end(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $service = app(VehicleUseService::class);
            $use = $service->plan($context, $customer->id, $customer->row_version, $input);
            $use = $service->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => $input['starts_at'],
                'odometer' => '100',
                'reason' => 'Collected',
            ]);

            $newVehicleId = DB::table('vehicles')->insertGetId([
                'tenant_id' => $context->tenantId,
                'vehicle_number' => 'REPLACEMENT-NEW',
                'registration_number' => 'REPLACEMENT-NEW',
                'status' => VehicleStatus::Active->value,
            ]);
            DB::table('vehicle_ownerships')->insert([
                'tenant_id' => $context->tenantId,
                'vehicle_id' => $newVehicleId,
                'owner_type' => VehicleOwnerType::Company->value,
                'owner_key' => 'company',
                'owner_code_snapshot' => 'COMPANY',
                'owner_name_snapshot' => 'Company',
                'ownership_type' => VehicleOwnershipType::CompanyOwned->value,
                'started_at' => '2026-09-01 00:00:00',
            ]);

            // Handover was at 09:00:00+05:30. Current time is 17:30:00+05:30. Replacement at 12:00:00+05:30.
            $validReplacementTime = '2026-09-07T12:00:00+05:30';
            $replacementInput = [
                'vehicle_id' => $newVehicleId,
                'owner_agreement_id' => null,
                'starts_at' => $validReplacementTime,
                'ends_at' => null,
                'return_odometer' => '150',
                'handover_odometer' => '500',
                'reason' => 'Scheduled exchange',
                'notes' => 'Customer requested swap',
            ];

            $next = $service->replace($context, $use->id, $use->row_version, $replacementInput);

            self::assertSame(VehicleUseStatus::Returned, $use->refresh()->status);
            self::assertSame(0, bccomp('150', (string) $use->return_odometer, 2));
            self::assertSame('2026-09-07 06:30:00', $use->returned_at->format('Y-m-d H:i:s'));

            self::assertSame(VehicleUseStatus::InCustody, $next->status);
            self::assertSame((int) $use->id, (int) $next->replaces_use_id);
            self::assertSame(0, bccomp('500', (string) $next->handover_odometer, 2));
            self::assertSame('2026-09-07 06:30:00', $next->handed_over_at->format('Y-m-d H:i:s'));
            self::assertSame('Customer requested swap', $next->notes);

            $this->assertDatabaseCount('vehicle_rental_uses', 2);
        });
    }

    public function test_replacement_at_current_time_with_clock_skew_succeeds_and_distant_future_is_rejected(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $service = app(VehicleUseService::class);
            $use = $service->plan($context, $customer->id, $customer->row_version, $input);
            $use = $service->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => $input['starts_at'],
                'odometer' => '100',
                'reason' => 'Collected',
            ]);

            $newVehicleId = DB::table('vehicles')->insertGetId([
                'tenant_id' => $context->tenantId,
                'vehicle_number' => 'REPLACEMENT-SKEW',
                'registration_number' => 'REPLACEMENT-SKEW',
                'status' => VehicleStatus::Active->value,
            ]);
            DB::table('vehicle_ownerships')->insert([
                'tenant_id' => $context->tenantId,
                'vehicle_id' => $newVehicleId,
                'owner_type' => VehicleOwnerType::Company->value,
                'owner_key' => 'company',
                'owner_code_snapshot' => 'COMPANY',
                'owner_name_snapshot' => 'Company',
                'ownership_type' => VehicleOwnershipType::CompanyOwned->value,
                'started_at' => '2026-09-01 00:00:00',
            ]);

            // Travel time is 2026-09-07T12:00:00+00:00 (17:30:00+05:30).
            // Replacement at 17:31:30+05:30 (90 seconds ahead, within 2-minute tolerance) succeeds.
            $skewReplacementTime = '2026-09-07T17:31:30+05:30';
            $next = $service->replace($context, $use->id, $use->row_version, [
                'vehicle_id' => $newVehicleId,
                'owner_agreement_id' => null,
                'starts_at' => $skewReplacementTime,
                'ends_at' => null,
                'return_odometer' => '160',
                'handover_odometer' => '600',
                'reason' => 'Skew exchange',
                'notes' => 'Tolerates clock skew',
            ]);

            self::assertSame(VehicleUseStatus::Returned, $use->refresh()->status);
            self::assertSame(VehicleUseStatus::InCustody, $next->status);
        });
    }
}
