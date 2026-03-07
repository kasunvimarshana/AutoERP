<?php

namespace App\Console\Commands;

use App\Messaging\RabbitMQConsumer;
use App\Saga\SagaOrchestrator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SagaEventConsumer extends Command
{
    protected $signature   = 'saga:consume';
    protected $description = 'Consume saga reply events from RabbitMQ and dispatch to the orchestrator';

    public function __construct(
        private readonly RabbitMQConsumer  $consumer,
        private readonly SagaOrchestrator  $orchestrator
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $queue = config('rabbitmq.queues.saga_replies', 'saga-replies');

        $this->info("[SagaConsumer] Listening on queue: {$queue}");
        Log::info('[SagaConsumer] Starting event consumer', ['queue' => $queue]);

        $this->consumer->consume($queue, function (array $message): void {
            $this->dispatch($message);
        });

        return self::SUCCESS;
    }

    private function dispatch(array $message): void
    {
        $type    = $message['type'] ?? '';
        $sagaId  = $message['saga_id'] ?? 'unknown';

        Log::info('[SagaConsumer] Dispatching message', ['type' => $type, 'saga_id' => $sagaId]);

        match ($type) {
            'INVENTORY_RESERVED',
            'INVENTORY_RESERVATION_FAILED' => $this->orchestrator->handleInventoryResponse($message),

            'PAYMENT_PROCESSED',
            'PAYMENT_FAILED'               => $this->orchestrator->handlePaymentResponse($message),

            'NOTIFICATION_SENT',
            'NOTIFICATION_FAILED'          => $this->orchestrator->handleNotificationResponse($message),

            default => Log::warning('[SagaConsumer] Unknown message type', [
                'type'    => $type,
                'saga_id' => $sagaId,
            ]),
        };
    }
}
