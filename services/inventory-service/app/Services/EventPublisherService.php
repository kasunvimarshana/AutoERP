<?php

namespace App\Services;

use App\Domain\Contracts\MessageBrokerInterface;
use App\Infrastructure\Messaging\EventEnvelope;
use Illuminate\Support\Facades\Log;

class EventPublisherService
{
    public function __construct(
        private readonly MessageBrokerInterface $broker,
    ) {
    }

    /**
     * Publish a domain event object to the message broker.
     *
     * The event object must have a toArray() method and an $eventType property.
     */
    public function publish(object $event): void
    {
        try {
            $topic      = config('queue.connections.rabbitmq.options.queue.exchange', 'inventory_events');
            $eventType  = property_exists($event, 'eventType') ? $event->eventType : get_class($event);
            $payload    = method_exists($event, 'toArray') ? $event->toArray() : (array) $event;

            $tenantId   = $payload['tenant_id'] ?? null;
            $envelope   = EventEnvelope::create(
                type:     $eventType,
                source:   'inventory-service',
                data:     $payload,
                tenantId: $tenantId,
            );

            $this->broker->publish($topic, $eventType, $envelope->toArray());
        } catch (\Throwable $e) {
            Log::error('EventPublisherService failed to publish event', [
                'event'   => get_class($event),
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            // Do NOT re-throw: event publishing failure is non-fatal for inventory ops
        }
    }

    /**
     * Publish multiple events as a batch.
     *
     * @param object[] $events
     */
    public function publishBatch(array $events): void
    {
        $topic    = config('queue.connections.rabbitmq.options.queue.exchange', 'inventory_events');
        $messages = [];

        foreach ($events as $event) {
            try {
                $eventType = property_exists($event, 'eventType') ? $event->eventType : get_class($event);
                $payload   = method_exists($event, 'toArray') ? $event->toArray() : (array) $event;
                $tenantId  = $payload['tenant_id'] ?? null;
                $envelope  = EventEnvelope::create(
                    type:     $eventType,
                    source:   'inventory-service',
                    data:     $payload,
                    tenantId: $tenantId,
                );

                $messages[] = [
                    'event'   => $eventType,
                    'payload' => $envelope->toArray(),
                ];
            } catch (\Throwable $e) {
                Log::error('EventPublisherService failed to serialize event for batch', [
                    'event' => get_class($event),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!empty($messages)) {
            try {
                $this->broker->publishBatch($topic, $messages);
            } catch (\Throwable $e) {
                Log::error('EventPublisherService batch publish failed', ['error' => $e->getMessage()]);
            }
        }
    }
}
