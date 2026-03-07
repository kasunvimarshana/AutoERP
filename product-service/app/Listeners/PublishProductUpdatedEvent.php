<?php

namespace App\Listeners;

use App\Events\ProductUpdated;
use App\Services\RabbitMQService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * PublishProductUpdatedEvent Listener
 *
 * Subscribes to the ProductUpdated domain event and publishes a message
 * to RabbitMQ with routing key "product.updated".
 *
 * The Inventory Service consumes this event to update inventory records
 * by product_name when a product is renamed.
 */
class PublishProductUpdatedEvent implements ShouldQueue
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
     * Publishes the product.updated event to RabbitMQ.
     */
    public function handle(ProductUpdated $event): void
    {
        try {
            $payload = $event->toPayload();

            $this->rabbitMQ->publish('product.updated', $payload);

            Log::info('Listener: Published product.updated event', [
                'product_id'   => $event->product->id,
                'name'         => $event->product->name,
                'previous_name' => $event->previousData['name'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Listener: Failed to publish product.updated event', [
                'product_id' => $event->product->id,
                'error'      => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ProductUpdated $event, \Throwable $exception): void
    {
        Log::error('Listener: Job failed for product.updated event', [
            'product_id' => $event->product->id,
            'error'      => $exception->getMessage(),
        ]);
    }
}
