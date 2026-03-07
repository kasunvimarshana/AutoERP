<?php

namespace Tests\Unit;

use App\Messaging\RabbitMQPublisher;
use App\Models\Order;
use App\Models\SagaState;
use App\Saga\SagaOrchestrator;
use App\Saga\Steps\ProcessPaymentStep;
use App\Saga\Steps\RefundPaymentStep;
use App\Saga\Steps\ReleaseInventoryStep;
use App\Saga\Steps\ReserveInventoryStep;
use App\Saga\Steps\SendNotificationStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Predis\Client as RedisClient;
use Tests\TestCase;

class SagaOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $publisherMock;
    private MockInterface $redisMock;
    private SagaOrchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publisherMock = Mockery::mock(RabbitMQPublisher::class);
        $this->redisMock     = Mockery::mock(RedisClient::class);

        $reserveStep      = new ReserveInventoryStep($this->publisherMock);
        $processStep      = new ProcessPaymentStep($this->publisherMock);
        $notifyStep       = new SendNotificationStep($this->publisherMock);
        $releaseStep      = new ReleaseInventoryStep($this->publisherMock);
        $refundStep       = new RefundPaymentStep($this->publisherMock);

        $this->orchestrator = new SagaOrchestrator(
            $reserveStep,
            $processStep,
            $notifyStep,
            $releaseStep,
            $refundStep,
            $this->redisMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createTestOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'customer_id'    => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'customer_email' => 'test@example.com',
            'items'          => [
                ['product_id' => \Ramsey\Uuid\Uuid::uuid4()->toString(), 'quantity' => 2, 'price' => 50.00],
            ],
            'total_amount'   => 100.00,
            'status'         => Order::STATUS_PENDING,
        ], $overrides));
    }

    public function testStartSagaCreatesStateRecord(): void
    {
        $order = $this->createTestOrder();

        $this->redisMock->shouldReceive('setex')->once()->andReturn('OK');
        $this->publisherMock->shouldReceive('publish')->once();

        $sagaId = $this->orchestrator->startSaga($order);

        $this->assertNotEmpty($sagaId);
        $this->assertDatabaseHas('saga_states', [
            'saga_id'     => $sagaId,
            'order_id'    => $order->id,
            'status'      => SagaState::STATUS_STARTED,
            'current_step' => 'RESERVE_INVENTORY',
        ]);
    }

    public function testHandleInventorySuccessTransitionsToPaymentStep(): void
    {
        $order = $this->createTestOrder();
        $sagaId = \Ramsey\Uuid\Uuid::uuid4()->toString();

        SagaState::create([
            'saga_id'           => $sagaId,
            'order_id'          => $order->id,
            'current_step'      => 'RESERVE_INVENTORY',
            'status'            => SagaState::STATUS_STARTED,
            'compensation_data' => [],
        ]);

        $order->update(['saga_id' => $sagaId]);

        $sagaData = json_encode([
            'saga_id'           => $sagaId,
            'order_id'          => $order->id,
            'customer_id'       => $order->customer_id,
            'total_amount'      => '100.00',
            'items'             => $order->items,
            'status'            => SagaState::STATUS_STARTED,
            'current_step'      => 'RESERVE_INVENTORY',
            'compensation_data' => [],
        ]);

        $this->redisMock->shouldReceive('get')->once()->andReturn($sagaData);
        $this->redisMock->shouldReceive('setex')->once()->andReturn('OK');
        $this->publisherMock->shouldReceive('publish')
            ->once()
            ->withArgs(fn ($exchange, $routingKey, $message) =>
                $routingKey === config('rabbitmq.queues.process_payment')
            );

        $this->orchestrator->handleInventoryResponse([
            'saga_id'  => $sagaId,
            'order_id' => $order->id,
            'type'     => 'INVENTORY_RESERVED',
            'success'  => true,
            'data'     => ['reservation_id' => 'res_123'],
        ]);

        $this->assertDatabaseHas('saga_states', [
            'saga_id' => $sagaId,
            'status'  => SagaState::STATUS_INVENTORY_RESERVED,
        ]);
    }

    public function testHandleInventoryFailureTriggersSagaFailure(): void
    {
        $order = $this->createTestOrder();
        $sagaId = \Ramsey\Uuid\Uuid::uuid4()->toString();

        SagaState::create([
            'saga_id'      => $sagaId,
            'order_id'     => $order->id,
            'current_step' => 'RESERVE_INVENTORY',
            'status'       => SagaState::STATUS_STARTED,
        ]);

        $order->update(['saga_id' => $sagaId]);

        $this->orchestrator->handleInventoryResponse([
            'saga_id'  => $sagaId,
            'order_id' => $order->id,
            'type'     => 'INVENTORY_RESERVATION_FAILED',
            'success'  => false,
            'error'    => 'Insufficient stock',
        ]);

        $this->assertDatabaseHas('saga_states', [
            'saga_id' => $sagaId,
            'status'  => SagaState::STATUS_FAILED,
        ]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_FAILED, $order->status);
    }

    public function testHandlePaymentSuccessTransitionsToNotificationStep(): void
    {
        $order = $this->createTestOrder();
        $sagaId = \Ramsey\Uuid\Uuid::uuid4()->toString();

        SagaState::create([
            'saga_id'           => $sagaId,
            'order_id'          => $order->id,
            'current_step'      => 'PROCESS_PAYMENT',
            'status'            => SagaState::STATUS_INVENTORY_RESERVED,
            'compensation_data' => ['inventory_reservation' => ['reservation_id' => 'res_123']],
        ]);

        $order->update(['saga_id' => $sagaId]);

        $sagaData = json_encode([
            'saga_id'           => $sagaId,
            'order_id'          => $order->id,
            'customer_id'       => $order->customer_id,
            'customer_email'    => $order->customer_email,
            'total_amount'      => '100.00',
            'items'             => $order->items,
            'status'            => SagaState::STATUS_INVENTORY_RESERVED,
            'current_step'      => 'PROCESS_PAYMENT',
            'compensation_data' => ['inventory_reservation' => ['reservation_id' => 'res_123']],
        ]);

        $this->redisMock->shouldReceive('get')->once()->andReturn($sagaData);
        $this->redisMock->shouldReceive('setex')->once()->andReturn('OK');
        $this->publisherMock->shouldReceive('publish')
            ->once()
            ->withArgs(fn ($exchange, $routingKey) =>
                $routingKey === config('rabbitmq.queues.send_notification')
            );

        $this->orchestrator->handlePaymentResponse([
            'saga_id'  => $sagaId,
            'order_id' => $order->id,
            'type'     => 'PAYMENT_PROCESSED',
            'success'  => true,
            'data'     => ['transaction_id' => 'txn_abc123', 'payment_id' => 'pay_456'],
        ]);

        $this->assertDatabaseHas('saga_states', [
            'saga_id' => $sagaId,
            'status'  => SagaState::STATUS_PAYMENT_PROCESSED,
        ]);
    }

    public function testHandlePaymentFailureTriggersCompensation(): void
    {
        $order = $this->createTestOrder();
        $sagaId = \Ramsey\Uuid\Uuid::uuid4()->toString();

        SagaState::create([
            'saga_id'           => $sagaId,
            'order_id'          => $order->id,
            'current_step'      => 'PROCESS_PAYMENT',
            'status'            => SagaState::STATUS_INVENTORY_RESERVED,
            'compensation_data' => ['inventory_reservation' => []],
        ]);

        $order->update(['saga_id' => $sagaId]);

        $sagaData = json_encode([
            'saga_id'           => $sagaId,
            'order_id'          => $order->id,
            'status'            => SagaState::STATUS_INVENTORY_RESERVED,
            'compensation_data' => ['inventory_reservation' => []],
        ]);

        $this->redisMock->shouldReceive('get')->andReturn($sagaData);
        $this->redisMock->shouldReceive('setex')->andReturn('OK');

        // Should publish release-inventory compensation command
        $this->publisherMock->shouldReceive('publish')
            ->atLeast()->once()
            ->withArgs(fn ($exchange, $routingKey) =>
                $routingKey === config('rabbitmq.queues.release_inventory')
            );

        $this->orchestrator->handlePaymentResponse([
            'saga_id'  => $sagaId,
            'order_id' => $order->id,
            'type'     => 'PAYMENT_FAILED',
            'success'  => false,
            'error'    => 'Card declined',
        ]);

        $order->refresh();
        $this->assertEquals(Order::STATUS_FAILED, $order->status);
    }

    public function testCompensateCallsReleaseInventory(): void
    {
        $order = $this->createTestOrder();
        $sagaId = \Ramsey\Uuid\Uuid::uuid4()->toString();

        SagaState::create([
            'saga_id'           => $sagaId,
            'order_id'          => $order->id,
            'current_step'      => 'PROCESS_PAYMENT',
            'status'            => SagaState::STATUS_INVENTORY_RESERVED,
            'compensation_data' => ['inventory_reservation' => ['reservation_id' => 'res_789']],
        ]);

        $sagaData = json_encode([
            'saga_id'           => $sagaId,
            'order_id'          => $order->id,
            'status'            => SagaState::STATUS_INVENTORY_RESERVED,
            'current_step'      => 'PROCESS_PAYMENT',
            'compensation_data' => ['inventory_reservation' => ['reservation_id' => 'res_789']],
        ]);

        $this->redisMock->shouldReceive('get')->once()->andReturn($sagaData);
        $this->redisMock->shouldReceive('setex')->once()->andReturn('OK');

        $this->publisherMock->shouldReceive('publish')
            ->once()
            ->withArgs(fn ($exchange, $routingKey) =>
                $routingKey === config('rabbitmq.queues.release_inventory')
            );

        $this->orchestrator->compensate($sagaId);
    }
}
