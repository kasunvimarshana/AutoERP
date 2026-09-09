<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\User\Services\UserAccessResolver;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Http\Resources\RunningChartRegisterResource;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RunningChartRegisterService;
use Modules\VehicleRental\Services\RunningChartService;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class RunningChartRegisterTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-09-10T12:00:00+00:00'));
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
            $mock->shouldReceive('assertChart')->zeroOrMoreTimes();
        });
    }

    public function test_register_filters_half_open_overlap_without_prorating_observations(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $charts = app(RunningChartService::class);
            $chart = $charts->create($context, $use->id, $use->row_version, $facts);
            $charts->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);
            $register = app(RunningChartRegisterService::class);
            $result = $register->list($context, ['search' => $use->vehicle_label_snapshot, 'chart_status' => RunningChartStatus::Finalized->value, 'from' => '2026-09-07T10:00:00+05:30', 'until' => '2026-09-07T11:00:00+05:30'], 10);
            self::assertSame(1, $result->total());
            $row = (new RunningChartRegisterResource($result->items()[0]))->toArray(new Request);
            self::assertSame('50.250000', $row['total_km']);
            self::assertSame($facts['starts_at'], $row['starts_at']);
            self::assertArrayNotHasKey('terms', $row['owner_agreement']);
            self::assertArrayHasKey('party_name', $row['customer_agreement']);
            self::assertSame(0, $register->list($context, ['until' => $facts['starts_at']], 10)->total());
            self::assertSame(0, $register->list($context, ['from' => $facts['ends_at']], 10)->total());
            self::assertSame(0, $register->list($context, ['chart_status' => RunningChartStatus::Draft->value], 10)->total());
            self::assertSame(1, $register->list($context, ['search' => $row['customer_agreement']['party_name']], 10)->total());
        });
    }

    public function test_search_cannot_escape_organization_or_tenant_scope(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            app(RunningChartService::class)->create($context, $use->id, $use->row_version, $facts);
            $otherOrg = OrganizationUnitFixture::create(['tenant_id' => $context->tenantId, 'code' => 'OTHER-REGISTER', 'name' => 'Other register']);
            $other = new AgreementContext($context->tenantId, $otherOrg, $context->actorId);
            self::assertSame(0, app(RunningChartRegisterService::class)->list($other, ['search' => $use->vehicle_label_snapshot], 10)->total());
            [$anotherTenant] = $this->fixture();
            self::assertSame(0, app(RunningChartRegisterService::class)->list($anotherTenant, ['search' => $facts['reference']], 10)->total());
        });
    }

    public function test_invalid_filters_fail_and_read_requires_chart_view_permission(): void
    {
        [$context] = $this->fixture();
        foreach ([['from' => '2026-09-31T10:00:00+00:00'], ['chart_status' => 'invented'], ['from' => '2026-09-07T12:00:00+00:00', 'until' => '2026-09-07T12:00:00+00:00']] as $filters) {
            try {
                app(RunningChartRegisterService::class)->list($context, $filters, 10);
                self::fail('Invalid filter accepted');
            } catch (ValidationException) {
                self::assertTrue(true);
            }
        }
        $service = new RunningChartRegisterService(new RentalAuthorization(app(UserAccessResolver::class)));
        $this->expectException(AuthorizationException::class);
        $service->list($context, [], 10);
    }
}
