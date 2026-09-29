<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\VehicleRental\Constants\RentalConfiguration;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Tests\TestCase;

final class AgreementTenantCalendarTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(RentalAuthorization::class, fn ($mock) => $mock->shouldReceive('assert')->zeroOrMoreTimes());
    }

    public function test_successor_can_activate_on_tenant_local_effective_date_before_utc_midnight(): void
    {
        // 19:00 UTC on September 30 is 00:30 on October 1 in Asia/Colombo.
        $this->travelTo(CarbonImmutable::parse('2026-09-30T19:00:00+00:00'));
        [$context, $input] = $this->fixture();
        $this->mock(ConfigurationResolverInterface::class, function ($mock) use ($context): void {
            $mock->shouldReceive('value')
                ->with(RentalConfiguration::WORKSPACE_TIMEZONE, $context->tenantId, $context->organizationUnitId)
                ->atLeast()->once()
                ->andReturn('Asia/Colombo');
        });

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $service = app(AgreementService::class);
            $predecessor = $service->create(AgreementKind::Customer, $context, $input);
            $predecessor = $service->change(AgreementKind::Customer, $context, $predecessor->id, $predecessor->row_version, AgreementAction::Activate);
            $successor = $service->successor(AgreementKind::Customer, $context, $predecessor->id, $predecessor->row_version, [
                'reference' => $predecessor->reference.'-R2',
                'agreed_on' => '2026-09-30',
                'starts_on' => '2026-10-01',
                'reason' => 'Effective-date boundary regression',
            ]);

            $successor = $service->change(AgreementKind::Customer, $context, $successor->id, $successor->row_version, AgreementAction::Activate);
            $predecessor = $predecessor->refresh();

            self::assertSame(AgreementStatus::Active, $successor->status);
            self::assertSame(AgreementStatus::Closed, $predecessor->status);
            self::assertSame('2026-09-30', $predecessor->ends_on->toDateString());
            self::assertSame('2026-10-01', $predecessor->closed_on->toDateString());
        });
    }
}
