<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\BaseRentPolicy;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\BaseRentBilling;
use Modules\VehicleRental\Services\BaseRentPreview;
use Modules\VehicleRental\Services\RentalAuthorization;
use Tests\TestCase;

final class AgreementClosureCoverageTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(RentalAuthorization::class, fn ($mock) => $mock->shouldReceive('assert', 'assertBilling')->zeroOrMoreTimes());
    }

    public function test_closed_open_ended_agreement_cannot_create_future_base_rent_coverage(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-20T12:00:00+00:00'));
        [$context, $input] = $this->fixture();

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $agreements = app(AgreementService::class);
            $agreement = $agreements->create(AgreementKind::Customer, $context, array_replace($input, [
                'basis' => 'daily',
                'starts_on' => '2026-09-07',
                'ends_on' => null,
                'terms' => ['base_rate' => '100'],
            ]));
            $agreement = $agreements->change(AgreementKind::Customer, $context, $agreement->id, $agreement->row_version, AgreementAction::Activate);
            $agreement = $agreements->change(AgreementKind::Customer, $context, $agreement->id, $agreement->row_version, AgreementAction::Close, reason: 'Rental ended');

            $preview = app(BaseRentPreview::class);
            $base = [
                'expected_version' => $agreement->row_version,
                'policy' => BaseRentPolicy::ActualCalendarDays->value,
                'from' => '2026-09-19',
                'until' => '2026-09-20',
            ];
            self::assertSame('200.000000', $preview->calculate(AgreementKind::Customer, $context, $agreement->id, $base)['base_rent']);

            try {
                $preview->calculate(AgreementKind::Customer, $context, $agreement->id, array_replace($base, ['until' => '2026-09-21']));
                self::fail('A closed agreement created future base-rent coverage.');
            } catch (ValidationException $error) {
                self::assertArrayHasKey('from', $error->errors());
            }

            try {
                app(BaseRentBilling::class)->create(AgreementKind::Customer, $context, $agreement->id, [
                    'expected_version' => $agreement->row_version,
                    'policy' => BaseRentPolicy::ActualCalendarDays->value,
                    'from' => '2026-09-20',
                    'until' => '2026-09-21',
                    'invoice_date' => '2026-09-20',
                    'exchange_rate' => '1',
                ]);
                self::fail('A closed agreement created a future base-rent charge.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('vehicle_rental_customer_base_charges', 0);
                $this->assertDatabaseCount('invoices', 0);
            }
        });
    }

    public function test_contract_end_remains_the_stricter_boundary_after_closure(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-20T12:00:00+00:00'));
        [$context, $input] = $this->fixture();

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $agreements = app(AgreementService::class);
            $agreement = $agreements->create(AgreementKind::Customer, $context, array_replace($input, [
                'basis' => 'daily',
                'starts_on' => '2026-09-07',
                'ends_on' => '2026-09-18',
                'terms' => ['base_rate' => '100'],
            ]));
            $agreement = $agreements->change(AgreementKind::Customer, $context, $agreement->id, $agreement->row_version, AgreementAction::Activate);
            $agreement = $agreements->change(AgreementKind::Customer, $context, $agreement->id, $agreement->row_version, AgreementAction::Close, reason: 'Administrative closure');

            $this->expectException(ValidationException::class);
            app(BaseRentPreview::class)->calculate(AgreementKind::Customer, $context, $agreement->id, [
                'expected_version' => $agreement->row_version,
                'policy' => BaseRentPolicy::ActualCalendarDays->value,
                'from' => '2026-09-18',
                'until' => '2026-09-19',
            ]);
        });
    }
}
