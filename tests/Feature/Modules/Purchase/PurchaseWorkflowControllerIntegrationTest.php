<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Purchase;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Purchase\Application\Contracts\Services\PurchaseWorkflowServiceInterface;
use Modules\Purchase\Domain\Constants\PurchaseErrorCode;
use Tests\TestCase;

final class PurchaseWorkflowControllerIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function testTransitionEndpointForwardsPayloadToWorkflowService(): void
    {
        $service = $this->createMock(PurchaseWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('transition')
            ->with(
                'purchase_order',
                '10',
                self::callback(static function (array $payload): bool {
                    return (string) ($payload['status'] ?? '') === 'submitted'
                        && (int) ($payload['actor_id'] ?? 0) === 42;
                }),
            )
            ->willReturn(Result::success(['ok' => true]));

        $this->app->instance(PurchaseWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/purchase/workflows/purchase_order/10/transition', [
                'status' => 'submitted',
                'actor_id' => 42,
            ]);

        $response->assertOk()
            ->assertJson(['data' => ['ok' => true]]);
    }

    public function testTransitionEndpointReturns422ForCancelWhenDependenciesExist(): void
    {
        $service = $this->createMock(PurchaseWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('transition')
            ->willReturn(Result::failure(new Error(
                PurchaseErrorCode::INVALID_VALUE,
                'Entity has downstream Purchase dependencies that must be finalized first.',
            )));

        $this->app->instance(PurchaseWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/purchase/workflows/purchase_order/10/transition', [
                'status' => 'cancelled',
                'actor_id' => 42,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Entity has downstream Purchase dependencies that must be finalized first.',
            ]);
    }

    public function testTransitionEndpointReturns422ForReversalAcknowledgementErrors(): void
    {
        $service = $this->createMock(PurchaseWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('transition')
            ->willReturn(Result::failure(new Error(
                PurchaseErrorCode::INVALID_VALUE,
                'inventory_reversed=true is required before status reversal.',
            )));

        $this->app->instance(PurchaseWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/purchase/workflows/grn_header/55/transition', [
                'status' => 'reversed',
                'reason' => 'rollback',
                'finance_reversed' => true,
                'actor_id' => 42,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'inventory_reversed=true is required before status reversal.',
            ]);
    }

    public function testCreateDocumentEndpointForwardsIdempotencyPayloadToWorkflowService(): void
    {
        $service = $this->createMock(PurchaseWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('createDocument')
            ->with(
                'purchase_order',
                '10',
                self::callback(static function (array $payload): bool {
                    return (string) ($payload['idempotency_key'] ?? '') === 'idem-doc-http-1'
                        && (string) ($payload['document_date'] ?? '') === '2026-05-28';
                }),
            )
            ->willReturn(Result::success(['document_id' => 1001]));

        $this->app->instance(PurchaseWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/purchase/workflows/purchase_order/10/document', [
            'document_date' => '2026-05-28',
            'idempotency_key' => 'idem-doc-http-1',
        ]);

        $response->assertOk()
            ->assertJson(['data' => ['document_id' => 1001]]);
    }

    public function testAllocatePaymentEndpointReturns422ForIdempotencyPayloadConflict(): void
    {
        $service = $this->createMock(PurchaseWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('allocatePayment')
            ->willReturn(Result::failure(new Error(
                PurchaseErrorCode::INVALID_VALUE,
                'idempotency_key is already used with a different request payload.',
            )));

        $this->app->instance(PurchaseWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/purchase/workflows/purchase_order/10/payment/allocate', [
            'allocated_amount' => 25,
            'idempotency_key' => 'idem-pay-http-1',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'idempotency_key is already used with a different request payload.',
            ]);
    }

    public function testPostInventoryEndpointForwardsPayloadToWorkflowService(): void
    {
        $service = $this->createMock(PurchaseWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('postInventory')
            ->with(
                'grn_header',
                '55',
                self::callback(static function (array $payload): bool {
                    return (string) ($payload['idempotency_key'] ?? '') === 'idem-inv-http-1'
                        && ! isset($payload['movement_type'])
                        && ! isset($payload['warehouse_id']);
                }),
            )
            ->willReturn(Result::success(['posted' => true]));

        $this->app->instance(PurchaseWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/purchase/workflows/grn_header/55/inventory/post', [
            'movement_type' => 'in',
            'warehouse_id' => 4,
            'idempotency_key' => 'idem-inv-http-1',
        ]);

        $response->assertOk()
            ->assertJson(['data' => ['posted' => true]]);
    }

    public function testReverseFinanceEndpointReturns404WhenEntityIsMissing(): void
    {
        $service = $this->createMock(PurchaseWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('reverseFinance')
            ->willReturn(Result::failure(new Error(
                PurchaseErrorCode::NOT_FOUND,
                'Purchase entity not found.',
            )));

        $this->app->instance(PurchaseWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/purchase/workflows/purchase_order/99/finance/reverse', [
            'journal_entry_id' => 100,
            'idempotency_key' => 'idem-rf-http-1',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Purchase entity not found.',
            ]);
    }

    public function testPostFinanceEndpointForwardsPayloadToWorkflowService(): void
    {
        $service = $this->createMock(PurchaseWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('postFinance')
            ->with(
                'purchase_order',
                '15',
                self::callback(static function (array $payload): bool {
                    return is_array($payload['entry_payload'] ?? null)
                        && is_array($payload['lines_payload'] ?? null)
                        && (string) ($payload['idempotency_key'] ?? '') === 'idem-fin-http-1';
                }),
            )
            ->willReturn(Result::success(['posted' => true]));

        $this->app->instance(PurchaseWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/purchase/workflows/purchase_order/15/finance/post', [
            'entry_payload' => ['memo' => 'accrual'],
            'lines_payload' => [['account_id' => 10, 'debit' => 50]],
            'idempotency_key' => 'idem-fin-http-1',
        ]);

        $response->assertOk()
            ->assertJson(['data' => ['posted' => true]]);
    }

    public function testPostFinanceEndpointReturns422ForIdempotencyPayloadConflict(): void
    {
        $service = $this->createMock(PurchaseWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('postFinance')
            ->willReturn(Result::failure(new Error(
                PurchaseErrorCode::INVALID_VALUE,
                'idempotency_key is already used with a different request payload.',
            )));

        $this->app->instance(PurchaseWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/purchase/workflows/purchase_order/15/finance/post', [
            'entry_payload' => ['memo' => 'changed'],
            'lines_payload' => [['account_id' => 11, 'debit' => 75]],
            'idempotency_key' => 'idem-fin-http-2',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'idempotency_key is already used with a different request payload.',
            ]);
    }
}
