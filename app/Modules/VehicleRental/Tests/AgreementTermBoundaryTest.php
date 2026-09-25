<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Tests\TestCase;

final class AgreementTermBoundaryTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(RentalAuthorization::class, fn ($mock) => $mock->shouldReceive('assert')->zeroOrMoreTimes());
    }

    public function test_security_deposit_requirement_is_customer_only(): void
    {
        [$context, $customer, $owner] = $this->fixture();
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customer, $owner): void {
            $service = app(AgreementService::class);
            $customerAgreement = $service->create(AgreementKind::Customer, $context, array_replace($customer, [
                'terms' => ['deposit_requirement' => '5000'],
            ]));
            self::assertSame('5000.000000', $customerAgreement->terms['deposit_requirement']);

            try {
                $service->create(AgreementKind::Owner, $context, array_replace($owner, [
                    'terms' => ['deposit_requirement' => '5000'],
                ]));
                self::fail('Owner agreement accepted a customer-only security deposit requirement.');
            } catch (ValidationException $error) {
                self::assertArrayHasKey('terms.deposit_requirement', $error->errors());
                $this->assertDatabaseCount('vehicle_rental_owner_agreements', 0);
            }
        });
    }
}
