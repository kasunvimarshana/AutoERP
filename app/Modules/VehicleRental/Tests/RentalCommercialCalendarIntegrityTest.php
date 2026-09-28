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
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Models\CustomerUsageCharge;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RentalChargePeriod;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\TestCase;

final class RentalCommercialCalendarIntegrityTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
        });
    }

    public function test_chart_charge_period_is_resolved_in_tenant_commercial_calendar(): void
    {
        [$context] = $this->fixture();
        $this->mockColomboCalendar($context->tenantId, $context->organizationUnitId);

        $charge = new CustomerUsageCharge;
        $charge->forceFill([
            'period_from' => '2026-09-30',
            'period_until' => '2026-09-30',
            'calculation' => [
                'chart' => [
                    'starts_at' => '2026-09-30T17:00:00+00:00',
                    'ends_at' => '2026-09-30T20:00:00+00:00',
                ],
            ],
        ]);

        self::assertSame(
            ['2026-09-30', '2026-10-01'],
            app(RentalChargePeriod::class)->resolve($charge, $context),
        );
    }

    public function test_successor_cutover_rejects_returned_use_that_crosses_tenant_local_midnight(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-30T21:00:00+00:00'));
        [$context, $customerInput, $ownerInput] = $this->fixture();
        $this->mockColomboCalendar($context->tenantId, $context->organizationUnitId);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerInput, $ownerInput): void {
            $agreements = app(AgreementService::class);
            $customer = $agreements->create(AgreementKind::Customer, $context, $customerInput);
            $owner = $agreements->create(AgreementKind::Owner, $context, $ownerInput);
            $customer = $agreements->change(AgreementKind::Customer, $context, $customer->id, $customer->row_version, AgreementAction::Activate);
            $owner = $agreements->change(AgreementKind::Owner, $context, $owner->id, $owner->row_version, AgreementAction::Activate);

            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $customer->id, $customer->row_version, [
                'vehicle_id' => $ownerInput['vehicle_id'],
                'owner_agreement_id' => $owner->id,
                'starts_at' => '2026-09-30T17:00:00+00:00',
                'ends_at' => '2026-09-30T20:00:00+00:00',
            ]);
            $use = $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-30T17:00:00+00:00',
                'reason' => 'Collected before local midnight',
            ]);
            $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::ReturnVehicle, [
                'occurred_at' => '2026-09-30T20:00:00+00:00',
                'reason' => 'Returned after local midnight',
            ]);

            $successor = $agreements->successor(AgreementKind::Customer, $context, $customer->id, $customer->row_version, [
                'reference' => $customer->reference.'-R2',
                'agreed_on' => '2026-09-30',
                'starts_on' => '2026-10-01',
                'reason' => 'Commercial revision at local midnight',
            ]);

            $this->expectException(ValidationException::class);
            $agreements->change(AgreementKind::Customer, $context, $successor->id, $successor->row_version, AgreementAction::Activate);
        });
    }

    private function mockColomboCalendar(int $tenantId, int $organizationUnitId): void
    {
        $this->mock(ConfigurationResolverInterface::class, function ($mock) use ($tenantId, $organizationUnitId): void {
            $mock->shouldReceive('value')
                ->with(RentalConfiguration::WORKSPACE_TIMEZONE, $tenantId, $organizationUnitId)
                ->atLeast()->once()
                ->andReturn('Asia/Colombo');
        });
    }
}
