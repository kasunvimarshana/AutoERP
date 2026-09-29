<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\VehicleRental\Constants\RentalConfiguration;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\OwnerSourceService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Tests\TestCase;

final class OwnerSourceTenantCalendarTest extends TestCase
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

    public function test_owner_source_lookup_uses_tenant_calendar_instead_of_client_offset_date(): void
    {
        [$context, , $ownerInput] = $this->fixture();
        $ownerInput['ends_on'] = '2026-09-07';

        $this->mock(ConfigurationResolverInterface::class, function ($mock) use ($context): void {
            $mock->shouldReceive('value')
                ->with(RentalConfiguration::WORKSPACE_TIMEZONE, $context->tenantId, $context->organizationUnitId)
                ->atLeast()->once()
                ->andReturn('Asia/Colombo');
        });

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerInput): void {
            $agreements = app(AgreementService::class);
            $owner = $agreements->create(AgreementKind::Owner, $context, $ownerInput);
            $owner = $agreements->change(
                AgreementKind::Owner,
                $context,
                $owner->id,
                $owner->row_version,
                AgreementAction::Activate,
            );

            $lookup = app(OwnerSourceService::class);

            // Client offset says September 8; Colombo commercial time is still September 7.
            self::assertSame(1, $lookup->list($context, $ownerInput['vehicle_id'], [
                'starts_at' => '2026-09-08T00:00:00+10:00',
                'ends_at' => '2026-09-08T01:00:00+10:00',
            ], 10)->total());

            // Client offset says September 7; Colombo commercial time is already September 8.
            self::assertSame(0, $lookup->list($context, $ownerInput['vehicle_id'], [
                'starts_at' => '2026-09-07T20:00:00-04:00',
                'ends_at' => '2026-09-07T21:00:00-04:00',
            ], 10)->total());
        });
    }
}
