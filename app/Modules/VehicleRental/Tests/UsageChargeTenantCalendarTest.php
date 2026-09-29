<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\VehicleRental\Constants\RentalConfiguration;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\UsageChargeComponent;
use Modules\VehicleRental\Enums\UsageChargePolicy;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Models\CustomerUsageCharge;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RunningChartService;
use Modules\VehicleRental\Services\UsageChargeBilling;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\TestCase;

final class UsageChargeTenantCalendarTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-09-12T12:00:00Z'));
        $this->mock(RentalAuthorization::class, fn ($mock) => $mock->shouldReceive('assert', 'assertUse', 'assertChart', 'assertBilling')->zeroOrMoreTimes());
    }

    public function test_usage_charge_uses_workspace_calendar_when_source_offset_is_a_day_ahead(): void
    {
        $this->assertCommercialPeriod(
            startsAt: '2026-09-08T00:00:00+10:00',
            endsAt: '2026-09-08T01:00:00+10:00',
            agreementDay: '2026-09-07',
            expectedFrom: '2026-09-07',
            expectedUntil: '2026-09-07',
        );
    }

    public function test_usage_charge_uses_workspace_calendar_when_source_offset_is_a_day_behind(): void
    {
        $this->assertCommercialPeriod(
            startsAt: '2026-09-07T20:00:00-04:00',
            endsAt: '2026-09-07T21:00:00-04:00',
            agreementDay: '2026-09-08',
            expectedFrom: '2026-09-08',
            expectedUntil: '2026-09-08',
        );
    }

    public function test_half_open_usage_ending_at_workspace_midnight_belongs_to_previous_commercial_day(): void
    {
        $this->assertCommercialPeriod(
            startsAt: '2026-09-07T23:00:00+05:30',
            endsAt: '2026-09-08T00:00:00+05:30',
            agreementDay: '2026-09-07',
            expectedFrom: '2026-09-07',
            expectedUntil: '2026-09-07',
        );
    }

    private function assertCommercialPeriod(
        string $startsAt,
        string $endsAt,
        string $agreementDay,
        string $expectedFrom,
        string $expectedUntil,
    ): void {
        [$context, $customerInput, $ownerInput] = $this->fixture();

        $this->mock(ConfigurationResolverInterface::class, function ($mock) use ($context): void {
            $mock->shouldReceive('value')
                ->with(RentalConfiguration::WORKSPACE_TIMEZONE, $context->tenantId, $context->organizationUnitId)
                ->atLeast()->once()
                ->andReturn('Asia/Colombo');
        });

        $this->withTenantExecutionContext($context->tenantId, function () use (
            $context,
            $customerInput,
            $ownerInput,
            $startsAt,
            $endsAt,
            $agreementDay,
            $expectedFrom,
            $expectedUntil,
        ): void {
            $agreements = app(AgreementService::class);
            $customerInput['starts_on'] = $agreementDay;
            $customerInput['ends_on'] = $agreementDay;
            $customerInput['terms'] = ['normal_ot_rate' => '500'];
            $ownerInput['starts_on'] = $agreementDay;
            $ownerInput['ends_on'] = $agreementDay;
            $ownerInput['terms'] = ['normal_ot_rate' => '250'];

            $customer = $agreements->create(AgreementKind::Customer, $context, $customerInput);
            $owner = $agreements->create(AgreementKind::Owner, $context, $ownerInput);
            $customer = $agreements->change(AgreementKind::Customer, $context, $customer->id, $customer->row_version, AgreementAction::Activate);
            $owner = $agreements->change(AgreementKind::Owner, $context, $owner->id, $owner->row_version, AgreementAction::Activate);

            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $customer->id, $customer->row_version, [
                'vehicle_id' => $ownerInput['vehicle_id'],
                'owner_agreement_id' => $owner->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
            $use = $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => $startsAt,
                'reason' => 'Collected',
            ]);

            $charts = app(RunningChartService::class);
            $chart = $charts->create($context, $use->id, $use->row_version, [
                'reference' => 'CAL-CHARGE-'.$agreementDay,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'normal_ot_minutes' => 60,
            ]);
            $chart = $charts->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);

            $invoice = app(UsageChargeBilling::class)->create(AgreementKind::Customer, $context, $chart->id, [
                'expected_version' => $customer->row_version,
                'expected_chart_version' => $chart->row_version,
                'invoice_date' => '2026-09-12',
                'exchange_rate' => '1',
                'policy' => UsageChargePolicy::RecordedMinutesAndNights->value,
                'component' => UsageChargeComponent::NormalOvertime->value,
            ]);

            $charge = CustomerUsageCharge::query()->sole();
            self::assertSame($expectedFrom, $charge->period_from->toDateString());
            self::assertSame($expectedUntil, $charge->period_until->toDateString());
            self::assertSame('Asia/Colombo', $charge->calculation['timezone']);
            self::assertSame($expectedFrom, $charge->calculation['supply_from']);
            self::assertSame($expectedUntil, $charge->calculation['supply_until']);
            self::assertSame($startsAt, $charge->calculation['chart']['starts_at']);
            self::assertSame($endsAt, $charge->calculation['chart']['ends_at']);
            self::assertSame($expectedFrom, $invoice->documentSnapshot->supply_period_start->toDateString());
            self::assertSame($expectedUntil, $invoice->documentSnapshot->supply_period_end->toDateString());
        });
    }
}
