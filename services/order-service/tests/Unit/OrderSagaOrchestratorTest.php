<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\SagaTransaction;
use App\Saga\OrderSagaOrchestrator;
use App\Saga\SagaStepResult;
use App\Saga\Steps\CreateOrderStep;
use App\Saga\Steps\ProcessPaymentStep;
use App\Saga\Steps\ReserveInventoryStep;
use App\Saga\Steps\SendNotificationStep;
use App\Services\RabbitMQService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class OrderSagaOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private OrderSagaOrchestrator $orchestrator;
    private RabbitMQService       $rabbitMQ;
    private CreateOrderStep       $createOrderStep;
    private ReserveInventoryStep  $reserveInventoryStep;
    private ProcessPaymentStep    $processPaymentStep;
    private SendNotificationStep  $sendNotificationStep;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rabbitMQ             = Mockery::mock(RabbitMQService::class);
        $this->createOrderStep      = new CreateOrderStep();
        $this->reserveInventoryStep = new ReserveInventoryStep($this->rabbitMQ);
        $this->processPaymentStep   = new ProcessPaymentStep($this->rabbitMQ);
        $this->sendNotificationStep = new SendNotificationStep($this->rabbitMQ);

        $this->orchestrator = new OrderSagaOrchestrator(
            $this->createOrderStep,
            $this->reserveInventoryStep,
            $this->processPaymentStep,
            $this->sendNotificationStep,
            $this->rabbitMQ,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // start()
    // =========================================================================

    /** @test */
    public function it_starts_the_saga_and_creates_an_order(): void
    {
        // RabbitMQ will be called to dispatch RESERVE_INVENTORY.
        $this->rabbitMQ
            ->shouldReceive('publishCommand')
            ->once()
            ->with('inventory', 'RESERVE_INVENTORY', Mockery::any());

        Redis::shouldReceive('setex')->zeroOrMoreTimes()->andReturn(true);
        Redis::shouldReceive('get')->zeroOrMoreTimes()->andReturn(null);

        $order = $this->orchestrator->start($this->sampleOrderData());

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals(Order::STATUS_PENDING, $order->status);
        $this->assertNotNull($order->saga_id);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
        $this->assertDatabaseHas('saga_transactions', [
            'order_id' => $order->id,
            'step'     => SagaTransaction::STEP_CREATE_ORDER,
            'status'   => SagaTransaction::STATUS_COMPLETED,
        ]);
    }

    /** @test */
    public function it_creates_a_saga_transaction_for_each_dispatched_step(): void
    {
        $this->rabbitMQ->shouldReceive('publishCommand')->once()->andReturn();
        Redis::shouldReceive('setex')->zeroOrMoreTimes()->andReturn(true);
        Redis::shouldReceive('get')->zeroOrMoreTimes()->andReturn(null);

        $order = $this->orchestrator->start($this->sampleOrderData());

        // CREATE_ORDER is synchronous → completed; RESERVE_INVENTORY is async → pending.
        $this->assertDatabaseHas('saga_transactions', [
            'order_id' => $order->id,
            'step'     => SagaTransaction::STEP_CREATE_ORDER,
            'status'   => SagaTransaction::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('saga_transactions', [
            'order_id' => $order->id,
            'step'     => SagaTransaction::STEP_RESERVE_INVENTORY,
            'status'   => SagaTransaction::STATUS_PENDING,
        ]);
    }

    // =========================================================================
    // handleStepSuccess()
    // =========================================================================

    /** @test */
    public function handle_step_success_advances_to_next_step(): void
    {
        $sagaId = (string) \Illuminate\Support\Str::uuid();

        $order = Order::create([
            'tenant_id'    => 1,
            'customer_id'  => 'cust-1',
            'status'       => Order::STATUS_PENDING,
            'total_amount' => '99.99',
            'currency'     => 'USD',
            'items'        => [['product_id' => 'p1', 'quantity' => 1, 'unit_price' => 99.99]],
            'metadata'     => [],
            'saga_id'      => $sagaId,
        ]);

        // Create a pending RESERVE_INVENTORY transaction.
        SagaTransaction::create([
            'order_id'   => $order->id,
            'saga_id'    => $sagaId,
            'step'       => SagaTransaction::STEP_RESERVE_INVENTORY,
            'status'     => SagaTransaction::STATUS_PENDING,
            'payload'    => [],
            'started_at' => now(),
        ]);

        // Seed saga state in Redis.
        $state = [
            'saga_id'         => $sagaId,
            'order_id'        => $order->id,
            'status'          => 'in_progress',
            'current_step'    => SagaTransaction::STEP_RESERVE_INVENTORY,
            'completed_steps' => [SagaTransaction::STEP_CREATE_ORDER],
            'step_results'    => [],
            'order_data'      => $this->sampleOrderData(),
        ];

        Redis::shouldReceive('get')
            ->with("saga:{$sagaId}")
            ->andReturn(json_encode($state));

        Redis::shouldReceive('setex')->zeroOrMoreTimes()->andReturn(true);

        // Expect PROCESS_PAYMENT command to be dispatched.
        $this->rabbitMQ
            ->shouldReceive('publishCommand')
            ->once()
            ->with('payment', 'PROCESS_PAYMENT', Mockery::any());

        $this->orchestrator->handleStepSuccess(
            $sagaId,
            SagaTransaction::STEP_RESERVE_INVENTORY,
            ['reservation_id' => 'res-123']
        );

        $this->assertDatabaseHas('saga_transactions', [
            'saga_id' => $sagaId,
            'step'    => SagaTransaction::STEP_RESERVE_INVENTORY,
            'status'  => SagaTransaction::STATUS_COMPLETED,
        ]);
    }

    /** @test */
    public function handle_step_success_completes_saga_on_last_step(): void
    {
        $sagaId = (string) \Illuminate\Support\Str::uuid();

        $order = Order::create([
            'tenant_id'    => 1,
            'customer_id'  => 'cust-1',
            'status'       => Order::STATUS_PENDING,
            'total_amount' => '99.99',
            'currency'     => 'USD',
            'items'        => [],
            'saga_id'      => $sagaId,
        ]);

        SagaTransaction::create([
            'order_id'   => $order->id,
            'saga_id'    => $sagaId,
            'step'       => SagaTransaction::STEP_SEND_NOTIFICATION,
            'status'     => SagaTransaction::STATUS_PENDING,
            'payload'    => [],
            'started_at' => now(),
        ]);

        $allSteps = OrderSagaOrchestrator::STEPS;
        $state    = [
            'saga_id'         => $sagaId,
            'order_id'        => $order->id,
            'status'          => 'in_progress',
            'current_step'    => SagaTransaction::STEP_SEND_NOTIFICATION,
            'completed_steps' => array_slice($allSteps, 0, 3),
            'step_results'    => [],
            'order_data'      => $this->sampleOrderData(),
        ];

        Redis::shouldReceive('get')
            ->with("saga:{$sagaId}")
            ->andReturn(json_encode($state));

        Redis::shouldReceive('setex')->zeroOrMoreTimes()->andReturn(true);

        // Expect order.confirmed event.
        $this->rabbitMQ
            ->shouldReceive('publishEvent')
            ->once()
            ->with('order.confirmed', Mockery::any());

        $this->orchestrator->handleStepSuccess(
            $sagaId,
            SagaTransaction::STEP_SEND_NOTIFICATION,
            ['notification_id' => 'notif-456']
        );

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => Order::STATUS_CONFIRMED,
        ]);
    }

    // =========================================================================
    // handleStepFailure() + compensation
    // =========================================================================

    /** @test */
    public function handle_step_failure_triggers_compensation(): void
    {
        $sagaId = (string) \Illuminate\Support\Str::uuid();

        $order = Order::create([
            'tenant_id'    => 1,
            'customer_id'  => 'cust-1',
            'status'       => Order::STATUS_PENDING,
            'total_amount' => '99.99',
            'currency'     => 'USD',
            'items'        => [],
            'saga_id'      => $sagaId,
        ]);

        SagaTransaction::create([
            'order_id'   => $order->id,
            'saga_id'    => $sagaId,
            'step'       => SagaTransaction::STEP_RESERVE_INVENTORY,
            'status'     => SagaTransaction::STATUS_PENDING,
            'payload'    => [],
            'started_at' => now(),
        ]);

        $state = [
            'saga_id'         => $sagaId,
            'order_id'        => $order->id,
            'status'          => 'in_progress',
            'current_step'    => SagaTransaction::STEP_RESERVE_INVENTORY,
            'completed_steps' => [SagaTransaction::STEP_CREATE_ORDER],
            'step_results'    => [SagaTransaction::STEP_CREATE_ORDER => ['order_id' => $order->id]],
            'order_data'      => $this->sampleOrderData(),
        ];

        Redis::shouldReceive('get')
            ->with("saga:{$sagaId}")
            ->andReturn(json_encode($state));

        Redis::shouldReceive('setex')->zeroOrMoreTimes()->andReturn(true);

        // Compensation of CREATE_ORDER is local (cancel order) – no publishCommand.
        // After compensation, order.failed event is emitted.
        $this->rabbitMQ
            ->shouldReceive('publishEvent')
            ->once()
            ->with('order.failed', Mockery::any());

        $this->orchestrator->handleStepFailure(
            $sagaId,
            SagaTransaction::STEP_RESERVE_INVENTORY,
            'Out of stock'
        );

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => Order::STATUS_FAILED,
        ]);
    }

    /** @test */
    public function it_returns_empty_array_for_unknown_saga(): void
    {
        Redis::shouldReceive('get')->andReturn(null);

        $state = $this->orchestrator->getSagaState('non-existent-saga-id');

        $this->assertIsArray($state);
        $this->assertEmpty($state);
    }

    // =========================================================================
    // nextStep (tested indirectly via STEPS constant)
    // =========================================================================

    /** @test */
    public function steps_constant_has_four_entries_in_correct_order(): void
    {
        $this->assertCount(4, OrderSagaOrchestrator::STEPS);
        $this->assertEquals('CREATE_ORDER',      OrderSagaOrchestrator::STEPS[0]);
        $this->assertEquals('RESERVE_INVENTORY', OrderSagaOrchestrator::STEPS[1]);
        $this->assertEquals('PROCESS_PAYMENT',   OrderSagaOrchestrator::STEPS[2]);
        $this->assertEquals('SEND_NOTIFICATION', OrderSagaOrchestrator::STEPS[3]);
    }

    // =========================================================================
    // SagaStepResult
    // =========================================================================

    /** @test */
    public function saga_step_result_success_factory(): void
    {
        $result = SagaStepResult::success(['order_id' => 42]);

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
        $this->assertEquals(42, $result->data['order_id']);
    }

    /** @test */
    public function saga_step_result_failure_factory(): void
    {
        $result = SagaStepResult::failure('Something went wrong');

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailure());
        $this->assertEquals('Something went wrong', $result->error);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function sampleOrderData(): array
    {
        return [
            'tenant_id'    => 1,
            'customer_id'  => 'cust-1',
            'items'        => [
                ['product_id' => 'prod-1', 'quantity' => 2, 'unit_price' => 49.99],
            ],
            'total_amount' => '99.98',
            'currency'     => 'USD',
            'metadata'     => ['source' => 'web'],
        ];
    }
}
