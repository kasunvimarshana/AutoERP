<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\VehicleRental\Constants\RentalConfiguration;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\UsageChargeComponent;
use Modules\VehicleRental\Enums\UsageChargePolicy;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RunningChartService;
use Modules\VehicleRental\Services\UsageChargeBilling;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\TestCase;

final class AgreementSuccessorCommercialCalendarTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(RentalAuthorization::class, fn ($mock) => $mock->shouldReceive('assert', 'assertUse', 'assertChart', 'assertBilling')->zeroOrMoreTimes());
    }

    public function test_successor_cutover_accepts_returned_use_and_charge_that_finish_before_workspace_boundary(): void
    {
        [$context, $customerInput, $ownerInput] = $this->fixture();
        $this->useColomboCalendar($context->tenantId, $context->organizationUnitId);
        $this->travelTo(CarbonImmutable::parse('2026-09-07T18:31:00Z'));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerInput, $ownerInput): void {
            $agreements = app(AgreementService::class);
            $customerInput['starts_on'] = '2026-09-07';
            $customerInput['ends_on'] = null;
            $customerInput['terms'] = ['normal_ot_rate' => '500'];
            $ownerInput['starts_on'] = '2026-09-07';
            $ownerInput['ends_on'] = null;
            $ownerInput['terms'] = ['normal_ot_rate' => '250'];

            $customer = $agreements->create(AgreementKind::Customer, $context, $customerInput);
            $owner = $agreements->create(AgreementKind::Owner, $context, $ownerInput);
            $customer = $agreements->change(AgreementKind::Customer, $context, $customer->id, $customer->row_version, AgreementAction::Activate);
            $owner = $agreements->change(AgreementKind::Owner, $context, $owner->id, $owner->row_version, AgreementAction::Activate);

            $startsAt = '2026-09-08T00:00:00+10:00';
            $endsAt = '2026-09-08T01:00:00+10:00';
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
                'reference' => 'CUTOVER-PREVIOUS-DAY',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'normal_ot_minutes' => 60,
            ]);
            $chart = $charts->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);
            $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::ReturnVehicle, [
                'occurred_at' => $endsAt,
                'reason' => 'Returned before revision boundary',
            ]);

            app(UsageChargeBilling::class)->create(AgreementKind::Customer, $context, $chart->id, [
                'expected_version' => $customer->row_version,
                'expected_chart_version' => $chart->row_version,
                'invoice_date' => '2026-09-08',
                'exchange_rate' => '1',
                'policy' => UsageChargePolicy::RecordedMinutesAndNights->value,
                'component' => UsageChargeComponent::NormalOvertime->value,
            ]);

            $successor = $agreements->successor(AgreementKind::Customer, $context, $customer->id, $customer->row_version, [
                'reference' => $customer->reference.'-R2',
                'agreed_on' => '2026-09-08',
                'starts_on' => '2026-09-08',
                'reason' => 'New commercial terms',
            ]);
            $successor = $agreements->change(AgreementKind::Customer, $context, $successor->id, $successor->row_version, AgreementAction::Activate);

            self::assertSame('active', $successor->status->value);
            self::assertSame('2026-09-07', $customer->refresh()->ends_on->toDateString());
        });
    }

    public function test_successor_cutover_rejects_use_that_crosses_workspace_boundary_even_when_source_offset_is_previous_day(): void
    {
        [$context, $customerInput, $ownerInput] = $this->fixture();
        $this->useColomboCalendar($context->tenantId, $context->organizationUnitId);
        $this->travelTo(CarbonImmutable::parse('2026-09-07T18:31:00Z'));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerInput, $ownerInput): void {
            $agreements = app(AgreementService::class);
            $customerInput['starts_on'] = '2026-09-07';
            $customerInput['ends_on'] = null;
            $ownerInput['starts_on'] = '2026-09-07';
            $ownerInput['ends_on'] = null;

            $customer = $agreements->create(AgreementKind::Customer, $context, $customerInput);
            $owner = $agreements->create(AgreementKind::Owner, $context, $ownerInput);
            $customer = $agreements->change(AgreementKind::Customer, $context, $customer->id, $customer->row_version, AgreementAction::Activate);
            $owner = $agreements->change(AgreementKind::Owner, $context, $owner->id, $owner->row_version, AgreementAction::Activate);

            app(VehicleUseService::class)->plan($context, $customer->id, $customer->row_version, [
                'vehicle_id' => $ownerInput['vehicle_id'],
                'owner_agreement_id' => $owner->id,
                'starts_at' => '2026-09-07T20:00:00-04:00',
                'ends_at' => '2026-09-07T21:00:00-04:00',
            ]);

            $successor = $agreements->successor(AgreementKind::Customer, $context, $customer->id, $customer->row_version, [
                'reference' => $customer->reference.'-R2',
                'agreed_on' => '2026-09-08',
                'starts_on' => '2026-09-08',
                'reason' => 'New commercial terms',
            ]);

            $this->expectException(ValidationException::class);
            $agreements->change(AgreementKind::Customer, $context, $successor->id, $successor->row_version, AgreementAction::Activate);
        });
    }

    private function useColomboCalendar(int $tenantId, int $organizationUnitId): void
    {
        $this->mock(ConfigurationResolverInterface::class, function ($mock) use ($tenantId, $organizationUnitId): void {
            $mock->shouldReceive('value')
                ->with(RentalConfiguration::WORKSPACE_TIMEZONE, $tenantId, $organizationUnitId)
                ->atLeast()->once()
                ->andReturn('Asia/Colombo');
        });
    }
}
