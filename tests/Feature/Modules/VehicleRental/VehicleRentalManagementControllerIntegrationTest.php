<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\VehicleRental;

use Modules\Core\Application\Results\Result;
use Modules\VehicleRental\Application\Contracts\Services\VehicleRentalManagementServiceInterface;
use Tests\TestCase;

final class VehicleRentalManagementControllerIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function testStoreAgreementAggregateForwardsPayloadToService(): void
    {
        $service = $this->createMock(VehicleRentalManagementServiceInterface::class);
        $service->expects(self::once())
            ->method('upsertAgreementAggregate')
            ->with(
                null,
                self::callback(static function (array $payload): bool {
                    return (string) ($payload['agreement_number'] ?? '') === 'AGR-1001'
                        && (int) ($payload['tenant_id'] ?? 0) === 1;
                }),
            )
            ->willReturn(Result::success(['id' => 10, 'agreement_number' => 'AGR-1001']));

        $this->app->instance(VehicleRentalManagementServiceInterface::class, $service);

        $response = $this->postJson('/api/vehicle-rental/agreements', [
            'tenant_id' => 1,
            'agreement_number' => 'AGR-1001',
        ]);

        $response->assertOk()->assertJson([
            'data' => ['id' => 10, 'agreement_number' => 'AGR-1001'],
        ]);
    }

    public function testVehicleAvailabilityEndpointForwardsFiltersToService(): void
    {
        $service = $this->createMock(VehicleRentalManagementServiceInterface::class);
        $service->expects(self::once())
            ->method('getVehicleAvailability')
            ->with(1, 55, '2026-05-20 08:00:00', '2026-05-20 18:00:00', 99)
            ->willReturn(Result::success([
                'available' => true,
                'conflicts' => [],
            ]));

        $this->app->instance(VehicleRentalManagementServiceInterface::class, $service);

        $response = $this->getJson(
            '/api/vehicle-rental/vehicle-availability?tenant_id=1&rental_vehicle_id=55&start_datetime=2026-05-20%2008:00:00&end_datetime=2026-05-20%2018:00:00&exclude_agreement_id=99',
        );

        $response->assertOk()->assertJson([
            'data' => ['available' => true, 'conflicts' => []],
        ]);
    }

    public function testProviderPayablesEndpointForwardsTenantAndAgreementToService(): void
    {
        $service = $this->createMock(VehicleRentalManagementServiceInterface::class);
        $service->expects(self::once())
            ->method('listProviderPayables')
            ->with(1, 77)
            ->willReturn(Result::success([
                ['id' => 301, 'status' => 'approved'],
            ]));

        $this->app->instance(VehicleRentalManagementServiceInterface::class, $service);

        $response = $this->getJson('/api/vehicle-rental/provider-payables?tenant_id=1&agreement_id=77');

        $response->assertOk()->assertJson([
            'data' => [
                ['id' => 301, 'status' => 'approved'],
            ],
        ]);
    }
}
