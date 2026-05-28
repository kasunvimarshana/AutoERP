<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Purchase;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Purchase\Application\Contracts\Services\PurchaseIntegrationServiceInterface;
use Modules\Purchase\Domain\Constants\PurchaseErrorCode;
use Tests\TestCase;

final class PurchaseIntegrationControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function testListDocumentsEndpointForwardsScopedPayload(): void
    {
        $service = $this->createMock(PurchaseIntegrationServiceInterface::class);
        $service->expects(self::once())
            ->method('listSourceDocuments')
            ->with(
                'purchase_order',
                '10',
                self::callback(static function (array $payload): bool {
                    return ! isset($payload['tenant_id']);
                }),
            )
            ->willReturn(Result::success([
                ['id' => 501, 'document_number' => 'PINV-0001'],
            ]));

        $this->app->instance(PurchaseIntegrationServiceInterface::class, $service);

        $response = $this->getJson('/api/purchase/integrations/workflows/purchase_order/10/documents');

        $response->assertOk()->assertJson([
            'data' => [
                ['id' => 501, 'document_number' => 'PINV-0001'],
            ],
        ]);
    }

    public function testCreatePaymentEndpointForwardsPayload(): void
    {
        $service = $this->createMock(PurchaseIntegrationServiceInterface::class);
        $service->expects(self::once())
            ->method('createSourcePayment')
            ->with(
                'purchase_order',
                '10',
                self::callback(static function (array $payload): bool {
                    return (float) ($payload['amount'] ?? 0) === 1500.0;
                }),
            )
            ->willReturn(Result::success([
                'payment' => ['id' => 3001],
                'allocated' => true,
            ]));

        $this->app->instance(PurchaseIntegrationServiceInterface::class, $service);

        $response = $this->postJson('/api/purchase/integrations/workflows/purchase_order/10/payments', [
            'amount' => 1500,
            'document_id' => 501,
        ]);

        $response->assertOk()->assertJson([
            'data' => [
                'payment' => ['id' => 3001],
                'allocated' => true,
            ],
        ]);
    }

    public function testCreateAdvanceEndpointForwardsPayload(): void
    {
        $service = $this->createMock(PurchaseIntegrationServiceInterface::class);
        $service->expects(self::once())
            ->method('createSourceAdvance')
            ->with(
                'purchase_order',
                '10',
                self::callback(static function (array $payload): bool {
                    return (float) ($payload['amount'] ?? 0) === 350.0
                        && (string) ($payload['advance_number'] ?? '') === 'ADV-1001';
                }),
            )
            ->willReturn(Result::success([
                'advance_payment' => ['id' => 7001],
                'allocated' => false,
            ]));

        $this->app->instance(PurchaseIntegrationServiceInterface::class, $service);

        $response = $this->postJson('/api/purchase/integrations/workflows/purchase_order/10/advances', [
            'amount' => 350,
            'advance_number' => 'ADV-1001',
        ]);

        $response->assertOk()->assertJson([
            'data' => [
                'advance_payment' => ['id' => 7001],
                'allocated' => false,
            ],
        ]);
    }

    public function testMatchDocumentLineEndpointForwardsPayload(): void
    {
        $service = $this->createMock(PurchaseIntegrationServiceInterface::class);
        $service->expects(self::once())
            ->method('matchSourceDocumentLine')
            ->with(
                'purchase_order',
                '10',
                501,
                self::callback(static function (array $payload): bool {
                    return (int) ($payload['source_line_id'] ?? 0) === 11
                        && (int) ($payload['document_line_id'] ?? 0) === 22
                        && (float) ($payload['linked_quantity'] ?? 0) === 3.0;
                }),
            )
            ->willReturn(Result::success(['match' => ['id' => 81]]));

        $this->app->instance(PurchaseIntegrationServiceInterface::class, $service);

        $response = $this->postJson(
            '/api/purchase/integrations/workflows/purchase_order/10/documents/501/lines/match',
            [
                'source_line_id' => 11,
                'document_line_id' => 22,
                'linked_quantity' => 3,
            ],
        );

        $response->assertOk()->assertJson(['data' => ['match' => ['id' => 81]]]);
    }

    public function testUnmatchDocumentLineEndpointForwardsPayload(): void
    {
        $service = $this->createMock(PurchaseIntegrationServiceInterface::class);
        $service->expects(self::once())
            ->method('unmatchSourceDocumentLine')
            ->with(
                'purchase_order',
                '10',
                501,
                self::callback(static function (array $payload): bool {
                    return (int) ($payload['link_id'] ?? 0) === 81;
                }),
            )
            ->willReturn(Result::success(['unmatched' => true]));

        $this->app->instance(PurchaseIntegrationServiceInterface::class, $service);

        $response = $this->postJson(
            '/api/purchase/integrations/workflows/purchase_order/10/documents/501/lines/unmatch',
            ['link_id' => 81],
        );

        $response->assertOk()->assertJson(['data' => ['unmatched' => true]]);
    }

    public function testApplyAdvanceEndpointForwardsPayload(): void
    {
        $service = $this->createMock(PurchaseIntegrationServiceInterface::class);
        $service->expects(self::once())
            ->method('applySourceAdvance')
            ->with(
                'purchase_order',
                '10',
                self::callback(static function (array $payload): bool {
                    return (float) ($payload['allocated_amount'] ?? 0) === 120.0;
                }),
            )
            ->willReturn(Result::success(['allocation_id' => 901]));

        $this->app->instance(PurchaseIntegrationServiceInterface::class, $service);

        $response = $this->postJson('/api/purchase/integrations/workflows/purchase_order/10/advances/apply', [
            'allocated_amount' => 120,
        ]);

        $response->assertOk()->assertJson([
            'data' => ['allocation_id' => 901],
        ]);
    }

    public function testPostPaymentEndpointReturns404ForNotFound(): void
    {
        $service = $this->createMock(PurchaseIntegrationServiceInterface::class);
        $service->expects(self::once())
            ->method('postPayment')
            ->willReturn(Result::failure(new Error(
                PurchaseErrorCode::NOT_FOUND,
                'Payment not found.',
            )));

        $this->app->instance(PurchaseIntegrationServiceInterface::class, $service);

        $response = $this->postJson('/api/purchase/integrations/payments/999/post', [
            'reason' => 'manual retry',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Payment not found.',
                'code' => PurchaseErrorCode::NOT_FOUND,
            ]);
    }

    public function testSupplierPayablesEndpointForwardsFilters(): void
    {
        $service = $this->createMock(PurchaseIntegrationServiceInterface::class);
        $service->expects(self::once())
            ->method('supplierPayables')
            ->with(0, null)
            ->willReturn(Result::success([
                ['supplier_id' => 88, 'outstanding_amount' => 120.0],
            ]));

        $this->app->instance(PurchaseIntegrationServiceInterface::class, $service);

        $response = $this->getJson('/api/purchase/integrations/suppliers/payables');

        $response->assertOk()->assertJson([
            'data' => [
                ['supplier_id' => 88, 'outstanding_amount' => 120.0],
            ],
        ]);
    }
}
