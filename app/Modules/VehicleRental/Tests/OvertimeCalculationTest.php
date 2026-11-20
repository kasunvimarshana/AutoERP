<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\DriverMode;
use Modules\VehicleRental\Http\Resources\RunningChartRegisterResource;
use Modules\VehicleRental\Http\Resources\VehicleUseResource;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Models\VehicleUse;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\OvertimeCalculator;
use Tests\TestCase;

final class OvertimeCalculationTest extends TestCase
{
    use BuildsRentalFixture;
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(\Modules\VehicleRental\Services\RentalAuthorization::class, fn ($mock) => $mock->shouldReceive('assert')->zeroOrMoreTimes()->shouldReceive('assertChart')->zeroOrMoreTimes()->shouldReceive('assertUse')->zeroOrMoreTimes());
    }

    public function test_overtime_calculator_standard_8_hour_baseline(): void
    {
        // 10 hours trip with standard 8 hour baseline -> 2 hours = 120 minutes OT
        $starts = '2026-11-20T08:00:00+05:30';
        $ends = '2026-11-20T18:00:00+05:30';

        $ot = OvertimeCalculator::calculateOtMinutes($starts, $ends, 8.0);
        self::assertSame(120, $ot);

        // 10.5 hours trip (10 hrs 30 mins) -> 150 minutes OT
        $endsHalf = '2026-11-20T18:30:00+05:30';
        self::assertSame(150, OvertimeCalculator::calculateOtMinutes($starts, $endsHalf, 8.0));

        // Exact 8 hours trip -> 0 minutes OT
        $endsExact = '2026-11-20T16:00:00+05:30';
        self::assertSame(0, OvertimeCalculator::calculateOtMinutes($starts, $endsExact, 8.0));

        // Under 8 hours trip (6 hours) -> 0 minutes OT
        $endsUnder = '2026-11-20T14:00:00+05:30';
        self::assertSame(0, OvertimeCalculator::calculateOtMinutes($starts, $endsUnder, 8.0));
    }

    public function test_overtime_calculator_custom_baseline_hours(): void
    {
        $starts = CarbonImmutable::parse('2026-11-20T08:00:00+05:30');
        $ends = CarbonImmutable::parse('2026-11-20T19:00:00+05:30'); // 11 hours = 660 mins

        // 9 hour baseline -> 2 hours = 120 mins OT
        self::assertSame(120, OvertimeCalculator::calculateOtMinutes($starts, $ends, 9.0));

        // 10 hour baseline -> 1 hour = 60 mins OT
        self::assertSame(60, OvertimeCalculator::calculateOtMinutes($starts, $ends, '10.000000'));

        // 11 hour baseline -> 0 mins OT
        self::assertSame(0, OvertimeCalculator::calculateOtMinutes($starts, $ends, 11));

        // Null or 0 baseline returns 0
        self::assertSame(0, OvertimeCalculator::calculateOtMinutes($starts, $ends, null));
        self::assertSame(0, OvertimeCalculator::calculateOtMinutes($starts, $ends, 0));

        // Null timestamps or invalid formats return 0 safely
        self::assertSame(0, OvertimeCalculator::calculateOtMinutes(null, null, 8.0));
        self::assertSame(0, OvertimeCalculator::calculateOtMinutes('invalid-start', 'invalid-end', 8.0));
        self::assertSame(0, OvertimeCalculator::calculateOtMinutes(null, $ends, 8.0));
        self::assertSame(0, OvertimeCalculator::calculateOtMinutes($starts, null, 8.0));

        // Invalid interval (end <= start) returns 0
        self::assertSame(0, OvertimeCalculator::calculateOtMinutes($ends, $starts, 8.0));
    }

    public function test_overtime_calculator_format_minutes(): void
    {
        self::assertSame('0 mins', OvertimeCalculator::formatMinutes(null));
        self::assertSame('0 mins', OvertimeCalculator::formatMinutes(0));
        self::assertSame('0 mins', OvertimeCalculator::formatMinutes(-15));
        self::assertSame('45 mins', OvertimeCalculator::formatMinutes(45));
        self::assertSame('1 min', OvertimeCalculator::formatMinutes(1));
        self::assertSame('1 hr', OvertimeCalculator::formatMinutes(60));
        self::assertSame('2 hrs', OvertimeCalculator::formatMinutes(120));
        self::assertSame('2 hrs 30 mins', OvertimeCalculator::formatMinutes(150));
        self::assertSame('1 hr 1 min', OvertimeCalculator::formatMinutes(61));
    }

    public function test_agreements_persist_configurable_working_hours_term_for_both_sides(): void
    {
        [$context, $customer, $owner] = $this->fixture();

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customer, $owner): void {
            $service = app(AgreementService::class);

            // Customer agreement with driver and 8.5 agreed working hours
            $customerInput = array_replace($customer, [
                'driver_mode' => DriverMode::WithDriver->value,
                'terms' => [
                    'working_hours' => '8.5',
                    'normal_ot_rate' => '1500.00',
                ],
            ]);
            $lessee = $service->create(AgreementKind::Customer, $context, $customerInput);
            self::assertSame('8.500000', $lessee->terms['working_hours']);
            self::assertSame('1500.000000', $lessee->terms['normal_ot_rate']);

            // Owner agreement with driver and 9.0 agreed working hours
            $ownerInput = array_replace($owner, [
                'driver_mode' => DriverMode::WithDriver->value,
                'terms' => [
                    'working_hours' => '9.0',
                    'normal_ot_rate' => '1200.00',
                ],
            ]);
            $lessor = $service->create(AgreementKind::Owner, $context, $ownerInput);
            self::assertSame('9.000000', $lessor->terms['working_hours']);
            self::assertSame('1200.000000', $lessor->terms['normal_ot_rate']);

            // Successor inherits working hours
            $service->change(AgreementKind::Customer, $context, $lessee->id, 1, AgreementAction::Activate);
            $successor = $service->successor(AgreementKind::Customer, $context, $lessee->id, 2, [
                'reference' => 'SUCC-WH',
                'agreed_on' => '2026-09-08',
                'starts_on' => '2026-10-01',
                'reason' => 'Contract extension',
            ]);
            self::assertSame('8.500000', $successor->terms['working_hours']);
        });
    }

    public function test_vehicle_use_and_chart_register_resources_expose_working_hours(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            // Update terms on customer and owner agreements to set working hours
            $use->customerAgreement->forceFill(['terms' => array_merge($use->customerAgreement->terms, ['working_hours' => '8.000000'])])->saveQuietly();
            $use->ownerAgreement->forceFill(['terms' => array_merge($use->ownerAgreement->terms, ['working_hours' => '9.000000'])])->saveQuietly();

            $useResource = (new VehicleUseResource($use->refresh()->load(['customerAgreement', 'ownerAgreement'])))->toArray(request());
            self::assertSame('8.000000', $useResource['customer_agreement']['working_hours']);
            self::assertSame('9.000000', $useResource['owner_agreement']['working_hours']);

            $chart = app(\Modules\VehicleRental\Services\RunningChartService::class)->create(
                $context,
                $use->id,
                $use->row_version,
                $facts,
            );

            $registerResource = (new RunningChartRegisterResource($chart->load(['vehicleUse.customerAgreement', 'vehicleUse.ownerAgreement', 'vehicleUse.vehicle'])))->toArray(request());
            self::assertSame('8.000000', $registerResource['customer_agreement']['working_hours']);
            self::assertSame('9.000000', $registerResource['owner_agreement']['working_hours']);
            self::assertSame('8.000000', $registerResource['vehicle_use']['customer_agreement']['working_hours']);
            self::assertSame('9.000000', $registerResource['vehicle_use']['owner_agreement']['working_hours']);
            self::assertSame('8.000000', $registerResource['agreed_baseline_hours']);
            self::assertSame(0, $registerResource['calculated_ot_minutes']);
        });
    }
}
