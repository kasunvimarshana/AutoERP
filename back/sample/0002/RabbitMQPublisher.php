<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

/**
 * RabbitMQPublisher
 *
 * Wraps php-amqplib to publish domain events to a fanout exchange so that
 * any subscriber (Node.js, Python, Java, Go …) can react to Product changes.
 *
 * Exchange type: FANOUT  → every bound queue receives every message.
 * Routing keys are embedded in the JSON body as `event_type` so consumers
 * can filter without topology changes.
 */
class RabbitMQPublisher
{
    private ?AMQPStreamConnection $connection = null;
    private string $exchange;

    public function __construct()
    {
        $this->exchange = config('rabbitmq.exchange', 'product_events');
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Publish a domain event.
     *
     * @param  string  $eventType  e.g. "product.created"
     * @param  array   $payload    Serialisable event data
     */
    public function publish(string $eventType, array $payload): void
    {
        try {
            $channel = $this->channel();

            // Declare exchange (idempotent – safe to call every time)
            $channel->exchange_declare(
                $this->exchange,
                'fanout',   // all bound queues receive the message
                false,      // passive
                true,       // durable
                false       // auto-delete
            );

            $body = json_encode([
                'event_type'  => $eventType,
                'occurred_at' => now()->toISOString(),
                'payload'     => $payload,
            ]);

            $msg = new AMQPMessage($body, [
                'content_type'  => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]);

            $channel->basic_publish($msg, $this->exchange);

            Log::info("[RabbitMQ] Published: {$eventType}", ['payload' => $payload]);
        } catch (\Throwable $e) {
            // Log but do NOT re-throw – a broker outage must not roll back the
            // local DB transaction. Use the outbox pattern in production.
            Log::error('[RabbitMQ] Publish failed', [
                'event' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function close(): void
    {
        $this->connection?->close();
        $this->connection = null;
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function channel()
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            $this->connection = new AMQPStreamConnection(
                config('rabbitmq.host',     'rabbitmq'),
                config('rabbitmq.port',     5672),
                config('rabbitmq.user',     'guest'),
                config('rabbitmq.password', 'guest'),
            );
        }

        return $this->connection->channel();
    }
}
