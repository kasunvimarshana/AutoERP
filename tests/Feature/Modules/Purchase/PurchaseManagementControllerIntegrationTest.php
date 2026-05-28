<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Purchase;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Purchase\Application\Contracts\Services\PurchaseManagementServiceInterface;
use Modules\Purchase\Domain\Constants\PurchaseErrorCode;
use Tests\TestCase;

final class PurchaseManagementControllerIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function testPurchaseOrderWithLinesEndpointForwardsPayload(): void
    {
        $service = $this->createMock(PurchaseManagementServiceInterface::class);
        $service->expects(self::once())
            ->method('upsertPurchaseOrderWithLines')
            ->with(
                null,
                self::callback(static function (array $payload): bool {
                    return (int) ($payload['tenant_id'] ?? 0) === 1
                        && is_array($payload['lines'] ?? null)
                        && count($payload['lines']) === 1;
                }),
            )
            ->willReturn(Result::success(['id' => 10]));

        $this->app->instance(PurchaseManagementServiceInterface::class, $service);

        $response = $this->postJson('/api/purchase/purchase-orders/with-lines', [
            'tenant_id' => 1,
            'supplier_id' => 7,
            'warehouse_id' => 9,
            'po_number' => 'PO-NEW-1',
            'order_date' => '2026-05-28',
            'lines' => [
                [
                    'item_id' => 20,
                    'uom_id' => 3,
                    'ordered_qty' => 5,
                    'unit_price' => 10,
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['data' => ['id' => 10]]);
    }

    public function testSyncPurchaseOrderLinesEndpointForwardsPayload(): void
    {
        $service = $this->createMock(PurchaseManagementServiceInterface::class);
        $service->expects(self::once())
            ->method('syncPurchaseOrderLines')
            ->with(
                15,
                self::callback(static function (array $payload): bool {
                    return (int) ($payload['tenant_id'] ?? 0) === 1
                        && is_array($payload['lines'] ?? null)
                        && count($payload['lines']) === 2;
                }),
            )
            ->willReturn(Result::success(['synced' => true]));

        $this->app->instance(PurchaseManagementServiceInterface::class, $service);

        $response = $this->putJson('/api/purchase/purchase-orders/15/lines/sync', [
            'tenant_id' => 1,
            'lines' => [
                ['id' => 100, 'ordered_qty' => 7],
                ['item_id' => 20, 'uom_id' => 3, 'ordered_qty' => 5, 'unit_price' => 10],
            ],
        ]);

        $response->assertOk()->assertJson(['data' => ['synced' => true]]);
    }

    public function testStatusHistoryEndpointReturns422OnValidationError(): void
    {
        $service = $this->createMock(PurchaseManagementServiceInterface::class);
        $service->expects(self::once())
            ->method('getStatusHistory')
            ->willReturn(Result::failure(new Error(
                PurchaseErrorCode::INVALID_VALUE,
                'tenant_id is required.',
            )));

        $this->app->instance(PurchaseManagementServiceInterface::class, $service);

        $response = $this->getJson('/api/purchase/workflows/purchase_order/10/history');

        $response->assertStatus(422)
            ->assertJson(['message' => 'tenant_id is required.']);
    }

    public function testSettingsShowEndpointForwardsTenantAndOrgFilters(): void
    {
        $service = $this->createMock(PurchaseManagementServiceInterface::class);
        $service->expects(self::once())
            ->method('getPurchaseSettings')
            ->with(1, 2)
            ->willReturn(Result::success(['id' => 1, 'tenant_id' => 1]));

        $this->app->instance(PurchaseManagementServiceInterface::class, $service);

        $response = $this->getJson('/api/purchase/settings?tenant_id=1&organization_unit_id=2');

        $response->assertOk()->assertJson(['data' => ['id' => 1, 'tenant_id' => 1]]);
    }

    public function testAvailableGrnLinesLookupEndpointForwardsParameters(): void
    {
        $service = $this->createMock(PurchaseManagementServiceInterface::class);
        $service->expects(self::once())
            ->method('getAvailableGrnLinesForDocument')
            ->with(1, 77)
            ->willReturn(Result::success([
                ['id' => 1, 'available_document_qty' => 3.0],
            ]));

        $this->app->instance(PurchaseManagementServiceInterface::class, $service);

        $response = $this->getJson('/api/purchase/lookups/grn-headers/77/available-document-lines?tenant_id=1');

        $response->assertOk()->assertJson([
            'data' => [
                ['id' => 1, 'available_document_qty' => 3.0],
            ],
        ]);
    }
}
