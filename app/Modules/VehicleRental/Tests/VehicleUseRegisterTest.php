<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Http\Resources\VehicleUseResource;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\VehicleUseRegisterService;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class VehicleUseRegisterTest extends TestCase
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
        });
    }

    public function test_planned_overlap_is_half_open_and_does_not_use_actual_custody_dates(): void
    {
        $this->custodyFixture(function ($context, $use): void {
            $register = app(VehicleUseRegisterService::class);
            self::assertSame(1, $register->list($context, ['use_status' => VehicleUseStatus::InCustody->value], 10)->total());
            self::assertSame(0, $register->list($context, ['use_status' => VehicleUseStatus::Planned->value], 10)->total());
            self::assertSame(0, $register->list($context, ['until' => $use->starts_at_input], 10)->total());
            self::assertSame(0, $register->list($context, ['from' => $use->ends_at_input], 10)->total());
            $result = $register->list($context, ['from' => '2026-09-07T10:00:00+05:30', 'until' => '2026-09-07T11:00:00+05:30'], 10);
            self::assertSame(1, $result->total());
            $row = (new VehicleUseResource($result->items()[0]))->toArray(new Request);
            self::assertSame($use->starts_at_input, $row['starts_at']);
            self::assertNull($row['returned_at']);
            self::assertArrayNotHasKey('terms', $row['owner_agreement']);
        });
    }

    public function test_open_ended_plan_survives_from_filter_and_search_cannot_escape_scope(): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input): void {
            $use = app(VehicleUseService::class)->plan($context, $customer->id, $customer->row_version, array_replace($input, ['ends_at' => null]));
            $register = app(VehicleUseRegisterService::class);
            self::assertSame(1, $register->list($context, ['from' => '2027-01-01T00:00:00+00:00'], 10)->total());
            $otherOrg = OrganizationUnitFixture::create(['tenant_id' => $context->tenantId, 'code' => 'OTHER-USE', 'name' => 'Other use']);
            $other = new AgreementContext($context->tenantId, $otherOrg, $context->actorId);
            [$anotherTenant] = $this->fixture();
            foreach ([$use->vehicle_label_snapshot, $customer->reference, $customer->party_name_snapshot, $owner->party_name_snapshot] as $search) {
                self::assertSame(1, $register->list($context, ['search' => $search], 10)->total());
                self::assertSame(0, $register->list($other, ['search' => $search], 10)->total());
                self::assertSame(0, $register->list($anotherTenant, ['search' => $search], 10)->total());
            }
        });
    }

    public function test_invalid_filters_are_rejected(): void
    {
        [$context] = $this->fixture();
        foreach ([['from' => '2026-09-31T10:00:00+00:00'], ['until' => '2026-09-07T10:00:00'], ['use_status' => 'invented'], ['from' => '2026-09-07T12:00:00+00:00', 'until' => '2026-09-07T12:00:00+00:00']] as $filters) {
            try {
                app(VehicleUseRegisterService::class)->list($context, $filters, 10);
                self::fail('Invalid filter accepted');
            } catch (ValidationException) {
                self::assertTrue(true);
            }
        }
    }
}
