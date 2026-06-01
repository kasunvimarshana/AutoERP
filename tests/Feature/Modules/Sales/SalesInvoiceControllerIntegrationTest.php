<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Sales;

use Modules\Core\Application\Results\Result;
use Modules\Sales\Application\Contracts\Services\SalesIntegrationServiceInterface;
use Modules\Sales\Application\Contracts\Services\SalesManagementServiceInterface;
use Tests\TestCase;

final class SalesInvoiceControllerIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_available_so_lines_for_invoice_uses_dedicated_management_method(): void
    {
        $integration = $this->createMock(SalesIntegrationServiceInterface::class);
        $management = $this->createMock(SalesManagementServiceInterface::class);

        $management->expects(self::once())
            ->method('getAvailableSalesOrderLinesForInvoice')
            ->with(1, 10)
            ->willReturn(Result::success([
                ['id' => 501, 'available_invoice_qty' => 4.5],
            ]));

        $this->app->instance(SalesIntegrationServiceInterface::class, $integration);
        $this->app->instance(SalesManagementServiceInterface::class, $management);

        $response = $this->getJson('/api/sales/available-so-lines-for-invoice?tenant_id=1&sales_order_id=10');

        $response->assertOk()->assertJson([
            'data' => [
                ['id' => 501, 'available_invoice_qty' => 4.5],
            ],
        ]);
    }

    public function test_available_so_lines_for_invoice_returns422_when_params_missing(): void
    {
        $integration = $this->createMock(SalesIntegrationServiceInterface::class);
        $management = $this->createMock(SalesManagementServiceInterface::class);

        $management->expects(self::never())->method('getAvailableSalesOrderLinesForInvoice');

        $this->app->instance(SalesIntegrationServiceInterface::class, $integration);
        $this->app->instance(SalesManagementServiceInterface::class, $management);

        $response = $this->getJson('/api/sales/available-so-lines-for-invoice?tenant_id=1');

        $response->assertStatus(422)->assertJson([
            'message' => 'tenant_id and sales_order_id are required.',
        ]);
    }

    public function test_store_from_so_forwards_to_integration_service(): void
    {
        $integration = $this->createMock(SalesIntegrationServiceInterface::class);
        $management = $this->createMock(SalesManagementServiceInterface::class);

        $integration->expects(self::once())
            ->method('createSourceDocument')
            ->with(
                'sales_order',
                10,
                self::callback(static function (array $payload): bool {
                    return (int) ($payload['sales_order_id'] ?? 0) === 10
                        && (string) ($payload['reference'] ?? '') === 'SO-INV-001';
                }),
            )
            ->willReturn(Result::success([
                'document_id' => 801,
                'source_type' => 'sales_order',
                'source_id' => 10,
            ]));

        $this->app->instance(SalesIntegrationServiceInterface::class, $integration);
        $this->app->instance(SalesManagementServiceInterface::class, $management);

        $response = $this->postJson('/api/sales/sales-invoices/from-so', [
            'sales_order_id' => 10,
            'reference' => 'SO-INV-001',
        ]);

        $response->assertOk()->assertJson([
            'data' => [
                'document_id' => 801,
                'source_type' => 'sales_order',
                'source_id' => 10,
            ],
        ]);
    }

    public function test_calculate_invoice_returns422_when_lines_missing(): void
    {
        $integration = $this->createMock(SalesIntegrationServiceInterface::class);
        $management = $this->createMock(SalesManagementServiceInterface::class);

        $management->expects(self::never())->method('calculateInvoicePreview');

        $this->app->instance(SalesIntegrationServiceInterface::class, $integration);
        $this->app->instance(SalesManagementServiceInterface::class, $management);

        $response = $this->postJson('/api/sales/calculate-invoice', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['lines']);
    }

    public function test_calculate_invoice_returns422_when_unit_price_missing(): void
    {
        $integration = $this->createMock(SalesIntegrationServiceInterface::class);
        $management = $this->createMock(SalesManagementServiceInterface::class);

        $management->expects(self::never())->method('calculateInvoicePreview');

        $this->app->instance(SalesIntegrationServiceInterface::class, $integration);
        $this->app->instance(SalesManagementServiceInterface::class, $management);

        $response = $this->postJson('/api/sales/calculate-invoice', [
            'lines' => [
                [
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['lines.0.unit_price']);
    }
}
