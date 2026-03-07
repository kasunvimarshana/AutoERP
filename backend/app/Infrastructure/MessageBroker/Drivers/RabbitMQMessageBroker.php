<?php

declare(strict_types=1);

namespace App\Infrastructure\MessageBroker\Drivers;

use App\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * RabbitMQ message broker driver using php-amqplib.
 *
 * Install: composer require php-amqplib/php-amqplib
 *
 * This driver uses the topic exchange pattern.
 * Topics map directly to routing keys.
 */
class RabbitMQMessageBroker implements MessageBrokerInterface
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public function __construct(private readonly array $config)
    {
        $this->connect();
    }

    private function connect(): void
    {
        try {
            $this->connection = new AMQPStreamConnection(
                $this->config['host'],
                (int) $this->config['port'],
                $this->config['user'],
                $this->config['password'],
                $this->config['vhost'] ?? '/',
            );

            $this->channel = $this->connection->channel();

            $exchange = $this->config['exchange'] ?? 'inventory_events';
            $this->channel->exchange_declare($exchange, 'topic', false, true, false);
        } catch (\Throwable $e) {
            Log::error("[RabbitMQMessageBroker] Connection failed: {$e->getMessage()}");
            $this->connection = null;
            $this->channel    = null;
        }
    }

    public function publish(string $topic, array $payload, array $options = []): void
    {
        if ($this->channel === null) {
            Log::warning("[RabbitMQ:offline] topic={$topic}", ['payload' => $payload]);
            return;
        }

        $body = json_encode([
            'topic'     => $topic,
            'payload'   => $payload,
            'timestamp' => now()->toIso8601String(),
            'id'        => $options['message_id'] ?? \Illuminate\Support\Str::uuid()->toString(),
        ]);

        $message = new AMQPMessage($body, [
            'content_type'  => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        $exchange = $this->config['exchange'] ?? 'inventory_events';
        $this->channel->basic_publish($message, $exchange, $topic);

        Log::debug("[RabbitMQMessageBroker] Published to {$topic}.");
    }

    public function subscribe(array $topics, callable $callback, array $options = []): void
    {
        if ($this->channel === null) {
            Log::warning('[RabbitMQMessageBroker] subscribe() called but channel is not available.');
            return;
        }

        $queue    = $options['queue'] ?? 'inventory_consumer';
        $exchange = $this->config['exchange'] ?? 'inventory_events';

        $this->channel->queue_declare($queue, false, true, false, false);

        foreach ($topics as $topic) {
            $this->channel->queue_bind($queue, $exchange, $topic);
        }

        $handler = static function (AMQPMessage $msg) use ($callback): void {
            $decoded = json_decode($msg->body, true) ?? [];
            $callback($decoded, $msg);
        };

        $this->channel->basic_consume($queue, '', false, false, false, false, $handler);

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function acknowledge(mixed $messageId): void
    {
        if ($this->channel !== null && $messageId instanceof AMQPMessage) {
            $messageId->ack();
        }
    }

    public function reject(mixed $messageId, bool $requeue = false): void
    {
        if ($this->channel !== null && $messageId instanceof AMQPMessage) {
            $messageId->nack($requeue);
        }
    }

    public function isHealthy(): bool
    {
        return $this->connection !== null && $this->connection->isConnected();
    }

    public function getDriver(): string
    {
        return 'rabbitmq';
    }

    public function __destruct()
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (\Throwable) {
            // Silently ignore destructor errors.
        }
    }
}
