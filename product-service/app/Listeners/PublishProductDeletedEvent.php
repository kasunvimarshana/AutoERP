<?php

namespace App\Listeners;

use App\Events\ProductDeleted;
use App\Services\RabbitMQService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * PublishProductDeletedEvent Listener
 *
 * Subscribes to the ProductDeleted domain event and publishes a message
 * to RabbitMQ with routing key "product.deleted".
 *
 * The Inventory Service consumes this event to delete ALL inventory records
 * associated with the deleted product (cross-service cascade delete).
 * This ensures data consistency across services even though there's no
 * shared database foreign key.
 */
class PublishProductDeletedEvent implements ShouldQueue
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
     * Publishes the product.deleted event to RabbitMQ.
     */
    public function handle(ProductDeleted $event): void
    {
        try {
            $payload = $event->toPayload();

            $this->rabbitMQ->publish('product.deleted', $payload);

            Log::info('Listener: Published product.deleted event', [
                'product_id'   => $event->productId,
                'product_name' => $event->productName,
            ]);
        } catch (\Exception $e) {
            Log::error('Listener: Failed to publish product.deleted event', [
                'product_id' => $event->productId,
                'error'      => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ProductDeleted $event, \Throwable $exception): void
    {
        Log::error('Listener: Job failed for product.deleted event', [
            'product_id' => $event->productId,
            'error'      => $exception->getMessage(),
        ]);
    }
}
