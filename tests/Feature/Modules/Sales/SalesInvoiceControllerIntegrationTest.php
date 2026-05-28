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

    public function testAvailableSoLinesForInvoiceUsesDedicatedManagementMethod(): void
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

    public function testAvailableSoLinesForInvoiceReturns422WhenParamsMissing(): void
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

    public function testStoreFromSoForwardsToIntegrationService(): void
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
}
