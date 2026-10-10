<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Modules\VehicleRental\Enums\IncidentReviewAction;
use Modules\VehicleRental\Enums\IncidentStatus;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RentalIncidentService;
use Modules\VehicleRental\Services\RunningChartService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class RentalIncidentServiceTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
            $mock->shouldReceive('assertChart')->zeroOrMoreTimes();
            $mock->shouldReceive('assertIncident')->zeroOrMoreTimes();
        });
    }

    public function test_incident_evidence_is_versioned_and_does_not_create_money(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $chart = app(RunningChartService::class)->create($context, $use->id, $use->row_version, $facts);
            $service = app(RentalIncidentService::class);
            $incident = $service->create($context, [
                'vehicle_use_id' => $use->id,
                'running_chart_id' => $chart->id,
                'incident_type' => 'fuel',
                'occurred_on' => '2026-09-07',
                'evidence_reference' => ' Receipt 101 ',
                'description' => 'Fuel purchased during customer use',
            ]);

            self::assertSame(IncidentStatus::Recorded, $incident->status);
            self::assertSame(1, $incident->row_version);
            self::assertSame($use->row_version, $incident->vehicle_use_version);
            self::assertSame('Receipt 101', $incident->evidence_reference);
            self::assertSame($chart->id, $incident->running_chart_id);
            self::assertSame(1, $service->list($context, 10)->total());
            self::assertSame(1, $service->history($context, $incident->id, 10)->total());
            self::assertSame(0, \Illuminate\Support\Facades\DB::table('invoices')->where('tenant_id', $context->tenantId)->count());

            $reviewed = $service->review($context, $incident->id, $incident->row_version, IncidentReviewAction::Confirm, 'Verified physical receipt');
            self::assertSame(IncidentStatus::Confirmed, $reviewed->status);
            self::assertSame(2, $reviewed->row_version);
            self::assertSame(2, $service->history($context, $incident->id, 10)->total());
            self::assertSame(0, \Illuminate\Support\Facades\DB::table('invoices')->where('tenant_id', $context->tenantId)->count());

            try {
                $reviewed->forceFill(['description' => 'Unapproved rewrite'])->save();
                self::fail('Immutable incident changed.');
            } catch (LogicException) {
                self::assertSame('Fuel purchased during customer use', $reviewed->refresh()->description);
            }
            $this->expectException(ConflictHttpException::class);
            $service->review($context, $incident->id, 1, IncidentReviewAction::Reject, 'Second attempt');
        });
    }

    public function test_chart_must_belong_to_selected_vehicle_use(): void
    {
        $this->custodyFixture(function ($context, $use): void {
            $this->expectException(ValidationException::class);
            app(RentalIncidentService::class)->create($context, [
                'vehicle_use_id' => $use->id,
                'running_chart_id' => 9999999,
                'incident_type' => 'repair',
                'occurred_on' => '2026-09-07',
                'evidence_reference' => 'Workshop estimate',
                'description' => 'Investigation',
            ]);
        });
    }

    public function test_other_organization_cannot_read_or_record_incident_on_vehicle_use(): void
    {
        $this->custodyFixture(function ($context, $use): void {
            $service = app(RentalIncidentService::class);
            $incident = $service->create($context, [
                'vehicle_use_id' => $use->id,
                'incident_type' => 'toll',
                'occurred_on' => '2026-09-07',
                'evidence_reference' => 'Ticket',
                'description' => 'Toll evidence',
            ]);
            $otherId = OrganizationUnitFixture::create([
                'tenant_id' => $context->tenantId,
                'code' => 'OTHER-INCIDENT', 'name' => 'Other Rental branch',
            ]);
            $other = new \Modules\VehicleRental\Data\AgreementContext($context->tenantId, $otherId, $context->actorId);
            self::assertSame(1, $service->vehicleUseOptions($context, $use->vehicle_label_snapshot, 10)->total());
            self::assertSame(0, $service->vehicleUseOptions($other, $use->vehicle_label_snapshot, 10)->total());
            self::assertSame(0, $service->list($other, 10)->total());
            try {
                $service->find($other, $incident->id);
                self::fail('Foreign organization incident was visible.');
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
                self::assertTrue(true);
            }
            $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
            $service->create($other, [
                'vehicle_use_id' => $use->id,
                'incident_type' => 'fuel',
                'occurred_on' => '2026-09-07',
                'evidence_reference' => 'Inaccessible use',
                'description' => 'Should not write',
            ]);
        });
    }
}
