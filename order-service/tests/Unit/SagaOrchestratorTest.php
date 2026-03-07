<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\SagaStepInterface;
use App\Exceptions\SagaException;
use App\Exceptions\SagaStepException;
use App\Saga\SagaContext;
use App\Saga\SagaOrchestrator;
use Mockery;
use Tests\TestCase;

/**
 * Tests for the SagaOrchestrator core logic.
 */
final class SagaOrchestratorTest extends TestCase
{
    /** @test */
    public function it_executes_all_steps_in_order(): void
    {
        $executionOrder = [];

        $step1 = $this->makeStep('Step1', function () use (&$executionOrder) {
            $executionOrder[] = 'Step1';
        });
        $step2 = $this->makeStep('Step2', function () use (&$executionOrder) {
            $executionOrder[] = 'Step2';
        });

        $orchestrator = (new SagaOrchestrator())->addStep($step1)->addStep($step2);
        $context = new SagaContext(['saga_id' => 'test-saga', 'order_id' => 'order-1']);

        $orchestrator->execute($context);

        $this->assertSame(['Step1', 'Step2'], $executionOrder);
    }

    /** @test */
    public function it_compensates_completed_steps_in_lifo_order_when_a_step_fails(): void
    {
        $compensationOrder = [];

        $step1 = $this->makeStep(
            'Step1',
            execute: fn () => null,
            compensate: function () use (&$compensationOrder) {
                $compensationOrder[] = 'Compensate-Step1';
            }
        );

        $step2 = $this->makeStep(
            'Step2',
            execute: fn () => null,
            compensate: function () use (&$compensationOrder) {
                $compensationOrder[] = 'Compensate-Step2';
            }
        );

        $failingStep = $this->makeStep(
            'FailingStep',
            execute: function () {
                throw new SagaStepException('Step failed');
            }
        );

        $orchestrator = (new SagaOrchestrator())
            ->addStep($step1)
            ->addStep($step2)
            ->addStep($failingStep);

        $context = new SagaContext(['saga_id' => 'test-saga', 'order_id' => 'order-1']);

        $this->expectException(SagaException::class);

        try {
            $orchestrator->execute($context);
        } finally {
            // LIFO order: Step2 compensated before Step1
            $this->assertSame(['Compensate-Step2', 'Compensate-Step1'], $compensationOrder);
        }
    }

    /** @test */
    public function saga_context_stores_and_retrieves_values(): void
    {
        $context = new SagaContext(['order_id' => 'order-123']);

        $context->set('payment_id', 'pay-456');

        $this->assertSame('order-123', $context->get('order_id'));
        $this->assertSame('pay-456', $context->get('payment_id'));
        $this->assertNull($context->get('nonexistent'));
        $this->assertSame('default', $context->get('nonexistent', 'default'));
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    private function makeStep(
        string   $name,
        callable $execute    = null,
        callable $compensate = null
    ): SagaStepInterface {
        $mock = Mockery::mock(SagaStepInterface::class);
        $mock->allows('getName')->andReturn($name);

        $mock->allows('execute')->andReturnUsing(
            $execute ?? fn (SagaContext $ctx) => null
        );

        $mock->allows('compensate')->andReturnUsing(
            $compensate ?? fn (SagaContext $ctx) => null
        );

        return $mock;
    }
}
