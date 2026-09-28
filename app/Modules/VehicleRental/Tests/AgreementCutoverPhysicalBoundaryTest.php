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
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\TestCase;

final class AgreementCutoverPhysicalBoundaryTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-01T12:00:00+00:00'));
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
        });
    }

    public function test_planned_use_crossing_tenant_midnight_cannot_hide_behind_client_offset(): void
    {
        [$context, $customerInput, $ownerInput] = $this->fixture();
        $this->useColomboCalendar($context->tenantId, $context->organizationUnitId);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerInput, $ownerInput): void {
            [$customer, $owner] = $this->activateAgreements($context, $customerInput, $ownerInput);
            app(VehicleUseService::class)->plan($context, $customer->id, $customer->row_version, [
                'vehicle_id' => $ownerInput['vehicle_id'],
                'owner_agreement_id' => $owner->id,
                'starts_at' => '2026-09-30T20:00:00-04:00',
                'ends_at' => '2026-10-01T00:00:00-04:00',
            ]);
            $successor = $this->successor($context, $customer, '2026-10-01');

            try {
                app(AgreementService::class)->change(
                    AgreementKind::Customer,
                    $context,
                    $successor->id,
                    $successor->row_version,
                    AgreementAction::Activate,
                );
                self::fail('Successor cut through a use that crosses tenant-local midnight.');
            } catch (ValidationException) {
                self::assertSame(AgreementStatus::Active, $customer->refresh()->status);
                self::assertSame(AgreementStatus::Draft, $successor->refresh()->status);
            }
        });
    }

    public function test_in_custody_use_blocks_cutover_even_when_planned_end_is_before_successor(): void
    {
        [$context, $customerInput, $ownerInput] = $this->fixture();
        $this->useColomboCalendar($context->tenantId, $context->organizationUnitId);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerInput, $ownerInput): void {
            [$customer, $owner] = $this->activateAgreements($context, $customerInput, $ownerInput);
            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $customer->id, $customer->row_version, [
                'vehicle_id' => $ownerInput['vehicle_id'],
                'owner_agreement_id' => $owner->id,
                'starts_at' => '2026-09-07T09:00:00+05:30',
                'ends_at' => '2026-09-08T09:00:00+05:30',
            ]);
            $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-07T09:00:00+05:30',
                'reason' => 'Collected',
            ]);
            $successor = $this->successor($context, $customer, '2026-10-01');

            $this->expectException(ValidationException::class);
            app(AgreementService::class)->change(
                AgreementKind::Customer,
                $context,
                $successor->id,
                $successor->row_version,
                AgreementAction::Activate,
            );
        });
    }

    public function test_actual_return_exactly_at_tenant_boundary_allows_adjacent_successor(): void
    {
        [$context, $customerInput, $ownerInput] = $this->fixture();
        $this->useColomboCalendar($context->tenantId, $context->organizationUnitId);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerInput, $ownerInput): void {
            [$customer, $owner] = $this->activateAgreements($context, $customerInput, $ownerInput);
            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $customer->id, $customer->row_version, [
                'vehicle_id' => $ownerInput['vehicle_id'],
                'owner_agreement_id' => $owner->id,
                'starts_at' => '2026-09-30T09:00:00+05:30',
                'ends_at' => '2026-10-02T09:00:00+05:30',
            ]);
            $use = $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-30T09:00:00+05:30',
                'reason' => 'Collected',
            ]);
            $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::ReturnVehicle, [
                'occurred_at' => '2026-10-01T00:00:00+05:30',
                'reason' => 'Returned at revision boundary',
            ]);
            $successor = $this->successor($context, $customer, '2026-10-01');

            $successor = app(AgreementService::class)->change(
                AgreementKind::Customer,
                $context,
                $successor->id,
                $successor->row_version,
                AgreementAction::Activate,
            );

            self::assertSame(AgreementStatus::Active, $successor->status);
            self::assertSame(AgreementStatus::Closed, $customer->refresh()->status);
            self::assertSame('2026-09-30', $customer->ends_on->toDateString());
        });
    }

    private function activateAgreements($context, array $customerInput, array $ownerInput): array
    {
        $service = app(AgreementService::class);
        $customer = $service->create(AgreementKind::Customer, $context, $customerInput);
        $owner = $service->create(AgreementKind::Owner, $context, $ownerInput);
        $customer = $service->change(AgreementKind::Customer, $context, $customer->id, $customer->row_version, AgreementAction::Activate);
        $owner = $service->change(AgreementKind::Owner, $context, $owner->id, $owner->row_version, AgreementAction::Activate);

        return [$customer, $owner];
    }

    private function successor($context, $predecessor, string $startsOn)
    {
        return app(AgreementService::class)->successor(AgreementKind::Customer, $context, $predecessor->id, $predecessor->row_version, [
            'reference' => $predecessor->reference.'-R2',
            'agreed_on' => '2026-09-30',
            'starts_on' => $startsOn,
            'reason' => 'Commercial revision',
        ]);
    }

    private function useColomboCalendar(int $tenantId, int $organizationUnitId): void
    {
        $this->mock(ConfigurationResolverInterface::class, function ($mock) use ($tenantId, $organizationUnitId): void {
            $mock->shouldReceive('value')
                ->with(RentalConfiguration::WORKSPACE_TIMEZONE, $tenantId, $organizationUnitId)
                ->zeroOrMoreTimes()
                ->andReturn('Asia/Colombo');
        });
    }
}
