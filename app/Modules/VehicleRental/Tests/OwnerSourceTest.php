<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\OwnerSourceService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class OwnerSourceTest extends TestCase
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

    public function test_sources_cover_the_full_planned_period_with_half_open_midnight_end(): void
    {
        [$context, , $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $agreements = app(AgreementService::class);
            $source = $agreements->create(AgreementKind::Owner, $context, array_replace($input, ['ends_on' => '2026-09-10']));
            $lookup = app(OwnerSourceService::class);
            $period = ['starts_at' => '2026-09-07T00:00:00+05:30', 'ends_at' => '2026-09-11T00:00:00+05:30'];
            self::assertSame(0, $lookup->list($context, $input['vehicle_id'], $period, 10)->total());
            $source = $agreements->change(AgreementKind::Owner, $context, $source->id, $source->row_version, AgreementAction::Activate);
            self::assertSame(1, $lookup->list($context, $input['vehicle_id'], $period, 10)->total());
            foreach ([['starts_at' => '2026-09-06T23:59:59+05:30'], ['ends_at' => '2026-09-11T00:00:01+05:30'], ['ends_at' => null]] as $outside) {
                self::assertSame(0, $lookup->list($context, $input['vehicle_id'], array_replace($period, $outside), 10)->total());
            }
            $agreements->change(AgreementKind::Owner, $context, $source->id, $source->row_version, AgreementAction::Close, reason: 'Source ended');
            self::assertSame(0, $lookup->list($context, $input['vehicle_id'], $period, 10)->total());
        });
    }

    public function test_open_ended_source_search_cannot_escape_vehicle_tenant_or_branch(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $period): void {
            $lookup = app(OwnerSourceService::class);
            $period['ends_at'] = null;
            $period['search'] = $owner->party_name_snapshot;
            self::assertSame(1, $lookup->list($context, $owner->vehicle_id, $period, 10)->total());
            $otherOrg = OrganizationUnitFixture::create(['tenant_id' => $context->tenantId, 'code' => 'SOURCE-OTHER', 'name' => 'Other source']);
            self::assertSame(0, $lookup->list(new AgreementContext($context->tenantId, $otherOrg, $context->actorId), $owner->vehicle_id, $period, 10)->total());
            [$foreign, , $foreignOwner] = $this->fixture();
            self::assertSame(0, $lookup->list($foreign, $owner->vehicle_id, $period, 10)->total());
            self::assertSame(0, $lookup->list($context, $foreignOwner['vehicle_id'], $period, 10)->total());
        });
    }

    public function test_missing_invalid_and_reversed_periods_fail_explicitly(): void
    {
        [$context, , $owner] = $this->fixture();
        foreach ([[], ['starts_at' => '2026-09-31T00:00:00+00:00', 'ends_at' => null], ['starts_at' => '2026-09-07T00:00:00+00:00', 'ends_at' => '2026-09-07T00:00:00+00:00']] as $input) {
            try {
                app(OwnerSourceService::class)->list($context, $owner['vehicle_id'], $input, 10);
                self::fail('Invalid planned source period accepted.');
            } catch (ValidationException) {
                self::assertTrue(true);
            }
        }
    }
}
