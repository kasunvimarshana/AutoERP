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
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\TestCase;

final class VehicleUseTenantCalendarTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-09-10T12:00:00Z'));
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
        });
    }

    public function test_vehicle_use_coverage_uses_tenant_calendar_instead_of_client_offset_date(): void
    {
        [$context, $customerInput, $ownerInput] = $this->fixture();
        $customerInput['ends_on'] = '2026-09-07';
        $ownerInput['ends_on'] = '2026-09-07';

        $this->mock(ConfigurationResolverInterface::class, function ($mock) use ($context): void {
            $mock->shouldReceive('value')
                ->with(RentalConfiguration::WORKSPACE_TIMEZONE, $context->tenantId, $context->organizationUnitId)
                ->atLeast()->once()
                ->andReturn('Asia/Colombo');
        });

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerInput, $ownerInput): void {
            $agreements = app(AgreementService::class);
            $customer = $agreements->create(AgreementKind::Customer, $context, $customerInput);
            $owner = $agreements->create(AgreementKind::Owner, $context, $ownerInput);
            $customer = $agreements->change(AgreementKind::Customer, $context, $customer->id, $customer->row_version, AgreementAction::Activate);
            $owner = $agreements->change(AgreementKind::Owner, $context, $owner->id, $owner->row_version, AgreementAction::Activate);

            $service = app(VehicleUseService::class);

            // The client offset says September 8, while the configured Colombo commercial
            // calendar sees 19:30-20:30 on September 7. This period is contract-covered.
            $covered = [
                'vehicle_id' => $ownerInput['vehicle_id'],
                'owner_agreement_id' => $owner->id,
                'starts_at' => '2026-09-08T00:00:00+10:00',
                'ends_at' => '2026-09-08T01:00:00+10:00',
            ];
            $use = $service->plan($context, $customer->id, $customer->row_version, $covered);
            $use = $service->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => $covered['starts_at'],
                'reason' => 'Collected within tenant-local contract date',
            ]);
            self::assertSame(VehicleUseStatus::InCustody, $use->status);
            $use = $service->transition($context, $use->id, $use->row_version, VehicleUseAction::ReturnVehicle, [
                'occurred_at' => $covered['ends_at'],
                'reason' => 'Returned after covered use',
            ]);
            self::assertSame(VehicleUseStatus::Returned, $use->status);

            // The client offset still says September 7, while Colombo sees September 8.
            // The same agreement must not be stretched into another commercial day.
            try {
                $service->plan($context, $customer->id, $customer->row_version, [
                    'vehicle_id' => $ownerInput['vehicle_id'],
                    'owner_agreement_id' => $owner->id,
                    'starts_at' => '2026-09-07T20:00:00-04:00',
                    'ends_at' => '2026-09-07T21:00:00-04:00',
                ]);
                self::fail('Vehicle use outside tenant-local agreement coverage was accepted.');
            } catch (ValidationException $error) {
                self::assertArrayHasKey('agreement', $error->errors());
            }

            $this->assertDatabaseCount('vehicle_rental_uses', 1);
        });
    }
}
