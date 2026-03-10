<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * EventPublisher
 *
 * RabbitMQ-backed event publisher.  Wraps PhpAmqpLib to provide a clean,
 * broker-agnostic interface for publishing domain events to the message bus.
 *
 * Connection details are read from config at runtime — no hardcoded values.
 */
class EventPublisher
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public function __construct(
        private readonly string $host,
        private readonly int    $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $vhost,
    ) {}

    /**
     * Publish an event to the given exchange with the specified routing key.
     *
     * @param  string               $exchange
     * @param  string               $routingKey
     * @param  array<string, mixed> $payload
     * @param  array<string, mixed> $headers
     * @return void
     */
    public function publish(
        string $exchange,
        string $routingKey,
        array  $payload,
        array  $headers = []
    ): void {
        try {
            $channel = $this->getChannel();

            $body = json_encode(
                array_merge($payload, [
                    'published_at' => now()->toIso8601String(),
                    'routing_key'  => $routingKey,
                ]),
                JSON_THROW_ON_ERROR
            );

            $message = new AMQPMessage($body, array_merge([
                'content_type'  => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'message_id'    => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'timestamp'     => time(),
            ], $headers));

            $channel->basic_publish($message, $exchange, $routingKey);

            Log::debug("Event published: {$routingKey}", ['exchange' => $exchange]);

        } catch (\Throwable $e) {
            Log::error("Failed to publish event [{$routingKey}]: {$e->getMessage()}", [
                'exchange' => $exchange,
                'payload'  => $payload,
            ]);

            // Do not rethrow — event publication failures should not break the
            // primary request flow.  Dead-letter queues handle reprocessing.
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Connection management
    // ─────────────────────────────────────────────────────────────────────────

    private function getChannel(): AMQPChannel
    {
        if ($this->channel === null || !$this->channel->is_open()) {
            $this->connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->password,
                $this->vhost
            );
            $this->channel = $this->connection->channel();
        }

        return $this->channel;
    }

    public function __destruct()
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (\Throwable) {
            // Ignore errors on teardown
        }
    }
}
