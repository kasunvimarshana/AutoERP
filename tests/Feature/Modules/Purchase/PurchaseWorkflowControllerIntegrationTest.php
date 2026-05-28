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
}
