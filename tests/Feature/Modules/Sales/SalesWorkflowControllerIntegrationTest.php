<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Sales;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Sales\Application\Contracts\Services\SalesWorkflowServiceInterface;
use Modules\Sales\Domain\Constants\SalesErrorCode;
use Tests\TestCase;

final class SalesWorkflowControllerIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_transition_endpoint_forwards_payload_to_workflow_service(): void
    {
        $service = $this->createMock(SalesWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('transition')
            ->with(
                'sales_order',
                '10',
                self::callback(static function (array $payload): bool {
                    return (string) ($payload['status'] ?? '') === 'submitted'
                        && (int) ($payload['actor_id'] ?? 0) === 42;
                }),
            )
            ->willReturn(Result::success(['ok' => true]));

        $this->app->instance(SalesWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/sales/workflows/sales_order/10/transition', [
            'status' => 'submitted',
            'actor_id' => 42,
        ]);

        $response->assertOk()->assertJson(['data' => ['ok' => true]]);
    }

    public function test_transition_endpoint_returns404_when_entity_is_missing(): void
    {
        $service = $this->createMock(SalesWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('transition')
            ->willReturn(Result::failure(new Error(
                SalesErrorCode::NOT_FOUND,
                'Sales entity not found.',
            )));

        $this->app->instance(SalesWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/sales/workflows/sales_order/999/transition', [
            'status' => 'submitted',
            'actor_id' => 42,
        ]);

        $response->assertStatus(404)->assertJson([
            'message' => 'Sales entity not found.',
        ]);
    }

    public function test_create_document_endpoint_forwards_idempotency_payload_to_workflow_service(): void
    {
        $service = $this->createMock(SalesWorkflowServiceInterface::class);
        $service->expects(self::once())
            ->method('createDocument')
            ->with(
                'sales_order',
                '10',
                self::callback(static function (array $payload): bool {
                    return (string) ($payload['idempotency_key'] ?? '') === 'idem-sales-doc-http-1'
                        && (string) ($payload['document_date'] ?? '') === '2026-05-28';
                }),
            )
            ->willReturn(Result::success(['document_id' => 2001]));

        $this->app->instance(SalesWorkflowServiceInterface::class, $service);

        $response = $this->postJson('/api/sales/workflows/sales_order/10/document', [
            'document_date' => '2026-05-28',
            'idempotency_key' => 'idem-sales-doc-http-1',
        ]);

        $response->assertOk()->assertJson(['data' => ['document_id' => 2001]]);
    }
}
