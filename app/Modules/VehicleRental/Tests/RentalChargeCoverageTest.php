<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Enums\UsageChargeComponent;
use Modules\VehicleRental\Enums\UsageChargePolicy;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RunningChartService;
use Modules\VehicleRental\Services\UsageChargeBilling;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\TestCase;

final class RentalChargeCoverageTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-09-12T12:00:00Z'));
        $this->mock(RentalAuthorization::class, fn ($mock) => $mock->shouldReceive('assert', 'assertUse', 'assertChart', 'assertBilling')->zeroOrMoreTimes());
    }

    public function test_physical_overrun_is_preserved_but_cannot_create_automatic_customer_or_owner_money_outside_contract_coverage(): void
    {
        [$context, $customerInput, $ownerInput] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerInput, $ownerInput): void {
            $agreements = app(AgreementService::class);
            $terms = ['normal_ot_rate' => '500'];
            $customerInput['ends_on'] = '2026-09-07';
            $customerInput['terms'] = $terms;
            $ownerInput['ends_on'] = '2026-09-07';
            $ownerInput['terms'] = $terms;

            $customer = $agreements->create(AgreementKind::Customer, $context, $customerInput);
            $owner = $agreements->create(AgreementKind::Owner, $context, $ownerInput);
            $customer = $agreements->change(AgreementKind::Customer, $context, $customer->id, $customer->row_version, AgreementAction::Activate);
            $owner = $agreements->change(AgreementKind::Owner, $context, $owner->id, $owner->row_version, AgreementAction::Activate);

            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $customer->id, $customer->row_version, [
                'vehicle_id' => $ownerInput['vehicle_id'],
                'owner_agreement_id' => $owner->id,
                'starts_at' => '2026-09-07T09:00:00+05:30',
                'ends_at' => '2026-09-07T23:00:00+05:30',
            ]);
            $use = $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-07T09:00:00+05:30',
                'reason' => 'Collected',
            ]);

            $charts = app(RunningChartService::class);
            $chart = $charts->create($context, $use->id, $use->row_version, [
                'reference' => 'OVERRUN-CHART',
                // Both inputs are September 7 in their source offset, but the workspace default UTC
                // calendar sees the half-open usage as September 7 through September 8.
                'starts_at' => '2026-09-07T19:00:00-04:00',
                'ends_at' => '2026-09-07T21:00:00-04:00',
                'normal_ot_minutes' => 60,
            ]);
            $chart = $charts->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);
            self::assertSame(RunningChartStatus::Finalized, $chart->status);

            $billing = app(UsageChargeBilling::class);
            foreach ([[AgreementKind::Customer, $customer], [AgreementKind::Owner, $owner]] as [$kind, $agreement]) {
                try {
                    $billing->create($kind, $context, $chart->id, [
                        'expected_version' => $agreement->row_version,
                        'expected_chart_version' => $chart->row_version,
                        'invoice_date' => '2026-09-12',
                        'exchange_rate' => '1',
                        'policy' => UsageChargePolicy::RecordedMinutesAndNights->value,
                        'component' => UsageChargeComponent::NormalOvertime->value,
                    ]);
                    self::fail('Automatic billing accepted physical evidence outside agreement coverage.');
                } catch (ValidationException $error) {
                    self::assertArrayHasKey('agreement', $error->errors());
                }
            }

            self::assertSame(RunningChartStatus::Finalized, $chart->refresh()->status);
            $this->assertDatabaseCount('vehicle_rental_customer_usage_charges', 0);
            $this->assertDatabaseCount('vehicle_rental_owner_usage_charges', 0);
            $this->assertDatabaseCount('invoices', 0);
        });
    }
}
