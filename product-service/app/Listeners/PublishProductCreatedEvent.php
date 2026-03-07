<?php

namespace App\Listeners;

use App\Events\ProductCreated;
use App\Services\RabbitMQService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * PublishProductCreatedEvent Listener
 *
 * Subscribes to the ProductCreated domain event and publishes a message
 * to RabbitMQ with routing key "product.created".
 *
 * Other services (Node.js Inventory Service, Python Analytics Service, etc.)
 * bind their queues to this routing key to react accordingly.
 *
 * Implements ShouldQueue so this runs asynchronously, decoupling the HTTP
 * response from the message broker publish.
 */
class PublishProductCreatedEvent implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The name of the queue the job should be sent to.
     */
    public string $queue = 'product_events';

    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly RabbitMQService $rabbitMQ
    ) {}

    /**
     * Handle the event.
     * Publishes the product.created event to RabbitMQ.
     */
    public function handle(ProductCreated $event): void
    {
        try {
            $payload = $event->toPayload();

            $this->rabbitMQ->publish('product.created', $payload);

            Log::info('Listener: Published product.created event', [
                'product_id' => $event->product->id,
                'name'       => $event->product->name,
            ]);
        } catch (\Exception $e) {
            Log::error('Listener: Failed to publish product.created event', [
                'product_id' => $event->product->id,
                'error'      => $e->getMessage(),
            ]);

            // Re-throw so the job queue can retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     * Logs the failure for manual intervention or dead-letter queue processing.
     */
    public function failed(ProductCreated $event, \Throwable $exception): void
    {
        Log::error('Listener: Job failed for product.created event', [
            'product_id' => $event->product->id,
            'error'      => $exception->getMessage(),
        ]);
    }
}
