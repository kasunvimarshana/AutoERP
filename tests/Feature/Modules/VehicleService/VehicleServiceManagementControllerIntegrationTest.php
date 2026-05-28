<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\VehicleService;

use Modules\Core\Application\Results\Result;
use Modules\VehicleService\Application\Contracts\Services\VehicleServiceManagementServiceInterface;
use Tests\TestCase;

final class VehicleServiceManagementControllerIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function testUpsertJobCardAggregateForwardsPayloadToService(): void
    {
        $service = $this->createMock(VehicleServiceManagementServiceInterface::class);
        $service->expects(self::once())
            ->method('upsertJobCardAggregate')
            ->with(
                null,
                self::callback(static function (array $payload): bool {
                    return (string) ($payload['job_card_number'] ?? '') === 'JC-1001'
                        && (int) ($payload['tenant_id'] ?? 0) === 1;
                }),
            )
            ->willReturn(Result::success(['id' => 10, 'job_card_number' => 'JC-1001']));

        $this->app->instance(VehicleServiceManagementServiceInterface::class, $service);

        $response = $this->postJson('/api/vehicle-service/job-cards/aggregate', [
            'tenant_id' => 1,
            'job_card_number' => 'JC-1001',
        ]);

        $response->assertOk()->assertJson([
            'data' => ['id' => 10, 'job_card_number' => 'JC-1001'],
        ]);
    }

    public function testStockAvailabilityEndpointForwardsFiltersToService(): void
    {
        $service = $this->createMock(VehicleServiceManagementServiceInterface::class);
        $service->expects(self::once())
            ->method('getStockAvailability')
            ->with(1, 55, 2, 9)
            ->willReturn(Result::success([
                'item_id' => 55,
                'stock_levels' => [],
            ]));

        $this->app->instance(VehicleServiceManagementServiceInterface::class, $service);

        $response = $this->getJson(
            '/api/vehicle-service/stock-availability?tenant_id=1&item_id=55&warehouse_id=2&location_id=9',
        );

        $response->assertOk()->assertJson([
            'data' => ['item_id' => 55, 'stock_levels' => []],
        ]);
    }

    public function testInvoiceableJobCardsEndpointForwardsTenantAndCustomerToService(): void
    {
        $service = $this->createMock(VehicleServiceManagementServiceInterface::class);
        $service->expects(self::once())
            ->method('getInvoiceableJobCards')
            ->with(1, 99)
            ->willReturn(Result::success([
                ['id' => 77, 'status' => 'completed'],
            ]));

        $this->app->instance(VehicleServiceManagementServiceInterface::class, $service);

        $response = $this->getJson('/api/vehicle-service/invoiceable-job-cards?tenant_id=1&customer_id=99');

        $response->assertOk()->assertJson([
            'data' => [
                ['id' => 77, 'status' => 'completed'],
            ],
        ]);
    }
}
