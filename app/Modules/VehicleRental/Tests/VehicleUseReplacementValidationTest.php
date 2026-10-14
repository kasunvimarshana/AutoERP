<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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
}
