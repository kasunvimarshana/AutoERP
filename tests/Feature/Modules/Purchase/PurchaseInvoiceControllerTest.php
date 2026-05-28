<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Purchase;

use Modules\Core\Application\Results\Result;
use Modules\Purchase\Application\Contracts\Services\PurchaseIntegrationServiceInterface;
use Modules\Purchase\Application\Contracts\Services\PurchaseManagementServiceInterface;
use Tests\TestCase;

final class PurchaseInvoiceControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function testUpdateInvoiceStatusForwardsToIntegrationService(): void
    {
        $integration = $this->createMock(PurchaseIntegrationServiceInterface::class);
        $integration->expects(self::once())
            ->method('changeSourceDocumentStatus')
            ->with(
                'purchase_order',
                10,
                501,
                self::callback(static function (array $payload): bool {
                    return (string) ($payload['status'] ?? '') === 'posted';
                }),
            )
            ->willReturn(Result::success(['id' => 501, 'status' => 'posted']));

        $management = $this->createMock(PurchaseManagementServiceInterface::class);

        $this->app->instance(PurchaseIntegrationServiceInterface::class, $integration);
        $this->app->instance(PurchaseManagementServiceInterface::class, $management);

        $response = $this->patchJson('/api/purchase/purchase-invoices/501', [
            'source_type' => 'purchase_order',
            'source_id' => 10,
            'status' => 'posted',
        ]);

        $response->assertOk()->assertJson([
            'data' => ['id' => 501, 'status' => 'posted'],
        ]);
    }

    public function testUpdateInvoiceDraftLinesSupportsReplaceAndCreate(): void
    {
        $integration = $this->createMock(PurchaseIntegrationServiceInterface::class);

        $integration->expects(self::once())
            ->method('unmatchSourceDocumentLine')
            ->with(
                'purchase_order',
                10,
                501,
                self::callback(static function (array $payload): bool {
                    return (int) ($payload['link_id'] ?? 0) === 90;
                }),
            )
            ->willReturn(Result::success(['unmatched' => true]));

        $matchCalls = 0;
        $integration->expects(self::exactly(2))
            ->method('matchSourceDocumentLine')
            ->willReturnCallback(static function (
                string $sourceType,
                int|string $sourceId,
                int $documentId,
                array $payload,
            ) use (&$matchCalls): Result {
                self::assertSame('purchase_order', $sourceType);
                self::assertSame(10, (int) $sourceId);
                self::assertSame(501, $documentId);

                if ($matchCalls === 0) {
                    self::assertSame(11, (int) ($payload['source_line_id'] ?? 0));
                    self::assertSame(21, (int) ($payload['document_line_id'] ?? 0));
                }

                if ($matchCalls === 1) {
                    self::assertSame(12, (int) ($payload['source_line_id'] ?? 0));
                    self::assertSame(22, (int) ($payload['document_line_id'] ?? 0));
                }

                $matchCalls++;

                return Result::success([
                    'match' => [
                        'id' => 800 + $matchCalls,
                    ],
                ]);
            });

        $management = $this->createMock(PurchaseManagementServiceInterface::class);

        $this->app->instance(PurchaseIntegrationServiceInterface::class, $integration);
        $this->app->instance(PurchaseManagementServiceInterface::class, $management);

        $response = $this->patchJson('/api/purchase/purchase-invoices/501', [
            'source_type' => 'purchase_order',
            'source_id' => 10,
            'lines' => [
                [
                    'link_id' => 90,
                    'source_line_id' => 11,
                    'document_line_id' => 21,
                    'linked_quantity' => 2,
                ],
                [
                    'source_line_id' => 12,
                    'document_line_id' => 22,
                    'linked_quantity' => 1,
                ],
            ],
        ]);

        $response->assertOk();
        $this->assertCount(2, (array) $response->json('data.line_changes'));
    }

    public function testCalculateInvoiceReturns422WhenLinesMissing(): void
    {
        $integration = $this->createMock(PurchaseIntegrationServiceInterface::class);
        $management = $this->createMock(PurchaseManagementServiceInterface::class);

        $management->expects(self::never())->method('calculateInvoicePreview');

        $this->app->instance(PurchaseIntegrationServiceInterface::class, $integration);
        $this->app->instance(PurchaseManagementServiceInterface::class, $management);

        $response = $this->postJson('/api/purchase/calculate-invoice', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['lines']);
    }

    public function testCalculateInvoiceReturns422WhenUnitPriceMissing(): void
    {
        $integration = $this->createMock(PurchaseIntegrationServiceInterface::class);
        $management = $this->createMock(PurchaseManagementServiceInterface::class);

        $management->expects(self::never())->method('calculateInvoicePreview');

        $this->app->instance(PurchaseIntegrationServiceInterface::class, $integration);
        $this->app->instance(PurchaseManagementServiceInterface::class, $management);

        $response = $this->postJson('/api/purchase/calculate-invoice', [
            'lines' => [
                [
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['lines.0.unit_price']);
    }
}
