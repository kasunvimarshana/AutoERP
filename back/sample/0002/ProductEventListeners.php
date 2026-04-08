<?php

namespace App\Listeners;

use App\Events\ProductCreated;
use App\Events\ProductUpdated;
use App\Events\ProductDeleted;
use App\Services\RabbitMQPublisher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

// ─────────────────────────────────────────────────────────────────────────────
// PublishProductCreated
// ─────────────────────────────────────────────────────────────────────────────
class PublishProductCreated implements ShouldQueue
{
    public string $queue = 'product-events';

    public function __construct(private readonly RabbitMQPublisher $publisher) {}

    public function handle(ProductCreated $event): void
    {
        $this->publisher->publish(
            'product.created',
            $event->product->toEventPayload()
        );

        Log::info('[Listener] product.created published', ['id' => $event->product->id]);
    }

    public function failed(ProductCreated $event, \Throwable $exception): void
    {
        Log::error('[Listener] Failed to publish product.created', [
            'id'    => $event->product->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// PublishProductUpdated
// ─────────────────────────────────────────────────────────────────────────────
class PublishProductUpdated implements ShouldQueue
{
    public string $queue = 'product-events';

    public function __construct(private readonly RabbitMQPublisher $publisher) {}

    public function handle(ProductUpdated $event): void
    {
        $this->publisher->publish('product.updated', [
            'current'  => $event->product->toEventPayload(),
            'previous' => $event->originalData,
        ]);
    }

    public function failed(ProductUpdated $event, \Throwable $exception): void
    {
        Log::error('[Listener] Failed to publish product.updated', [
            'error' => $exception->getMessage(),
        ]);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// PublishProductDeleted
// ─────────────────────────────────────────────────────────────────────────────
class PublishProductDeleted implements ShouldQueue
{
    public string $queue = 'product-events';

    public function __construct(private readonly RabbitMQPublisher $publisher) {}

    public function handle(ProductDeleted $event): void
    {
        $this->publisher->publish('product.deleted', $event->productSnapshot);
    }

    public function failed(ProductDeleted $event, \Throwable $exception): void
    {
        Log::error('[Listener] Failed to publish product.deleted', [
            'error' => $exception->getMessage(),
        ]);
    }
}
