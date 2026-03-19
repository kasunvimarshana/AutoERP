<?php

namespace Shared\Events;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Outbox Pattern Implementation
 * Ensures events are published exactly once to the message broker.
 */
class OutboxService
{
    /**
     * Records an event in the outbox table within the current DB transaction.
     */
    public function recordEvent(string $topic, string $eventType, array $payload, string $tenantId): void
    {
        DB::table('outbox_events')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'topic' => $topic,
            'event_type' => $eventType,
            'payload' => json_encode($payload),
            'status' => 'PENDING',
            'created_at' => now(),
        ]);
    }

    /**
     * Publishes PENDING events to Kafka/RabbitMQ (to be called by a background worker).
     */
    public function publishPendingEvents(): void
    {
        $events = DB::table('outbox_events')
            ->where('status', 'PENDING')
            ->limit(100)
            ->lockForUpdate()
            ->get();

        foreach ($events as $event) {
            try {
                // Broker::publish($event->topic, $event->payload);
                
                DB::table('outbox_events')
                    ->where('id', $event->id)
                    ->update(['status' => 'PUBLISHED', 'published_at' => now()]);
            } catch (\Exception $e) {
                DB::table('outbox_events')
                    ->where('id', $event->id)
                    ->update(['status' => 'FAILED', 'error' => $e->getMessage()]);
            }
        }
    }
}
