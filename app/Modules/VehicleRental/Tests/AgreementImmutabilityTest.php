<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Tests\TestCase;

final class AgreementImmutabilityTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(RentalAuthorization::class, fn ($mock) => $mock->shouldReceive('assert')->zeroOrMoreTimes());
    }

    public function test_closed_agreement_end_date_cannot_be_rewritten_after_transition(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $service = app(AgreementService::class);
            $agreement = $service->create(AgreementKind::Customer, $context, $input);
            $agreement = $service->change(AgreementKind::Customer, $context, $agreement->id, $agreement->row_version, AgreementAction::Activate);
            $agreement = $service->change(AgreementKind::Customer, $context, $agreement->id, $agreement->row_version, AgreementAction::Close, reason: 'Completed');

            $this->expectException(LogicException::class);
            $agreement->ends_on = '2027-01-01';
            $agreement->save();
        });
    }

    public function test_recorded_closure_date_cannot_be_rewritten_after_transition(): void
    {
        [$context, $input] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $input): void {
            $service = app(AgreementService::class);
            $agreement = $service->create(AgreementKind::Customer, $context, $input);
            $agreement = $service->change(AgreementKind::Customer, $context, $agreement->id, $agreement->row_version, AgreementAction::Activate);
            $agreement = $service->change(AgreementKind::Customer, $context, $agreement->id, $agreement->row_version, AgreementAction::Close, reason: 'Completed');

            $this->expectException(LogicException::class);
            $agreement->closed_on = $agreement->closed_on->subDay();
            $agreement->save();
        });
    }
}
