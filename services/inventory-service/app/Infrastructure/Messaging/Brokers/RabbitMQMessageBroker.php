<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Brokers;

use App\Contracts\Messaging\MessageBrokerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

/**
 * RabbitMQ Message Broker Implementation
 */
class RabbitMQMessageBroker implements MessageBrokerInterface
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $vhost,
        private readonly LoggerInterface $logger
    ) {}

    public function publish(string $topic, array $message, array $options = []): bool
    {
        try {
            $channel = $this->getChannel();
            $exchange = $options['exchange'] ?? 'inventory.events';
            $routingKey = $options['routing_key'] ?? $topic;
            $channel->exchange_declare($exchange, 'topic', false, true, false);
            $body = json_encode([
                'event' => $topic,
                'payload' => $message,
                'timestamp' => now()->toISOString(),
                'message_id' => (string) \Illuminate\Support\Str::uuid(),
            ]);
            $msg = new AMQPMessage($body, [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_type' => 'application/json',
            ]);
            $channel->basic_publish($msg, $exchange, $routingKey);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('RabbitMQ publish failed', ['error' => $e->getMessage(), 'topic' => $topic]);
            return false;
        }
    }

    public function subscribe(string $topic, callable $handler, array $options = []): void
    {
        $channel = $this->getChannel();
        $queue = $options['queue'] ?? $topic;
        $exchange = $options['exchange'] ?? 'inventory.events';
        $channel->exchange_declare($exchange, 'topic', false, true, false);
        $channel->queue_declare($queue, false, true, false, false);
        $channel->queue_bind($queue, $exchange, $topic);
        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($queue, '', false, false, false, false, function (AMQPMessage $msg) use ($handler) {
            $data = json_decode($msg->getBody(), true) ?? [];
            $handler($data, $msg);
        });
        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    public function acknowledge(mixed $message): void
    {
        if ($message instanceof AMQPMessage) {
            $message->ack();
        }
    }

    public function reject(mixed $message, bool $requeue = false): void
    {
        if ($message instanceof AMQPMessage) {
            $message->nack($requeue);
        }
    }

    public function isConnected(): bool
    {
        return $this->connection?->isConnected() ?? false;
    }

    public function disconnect(): void
    {
        $this->channel?->close();
        $this->connection?->close();
        $this->channel = null;
        $this->connection = null;
    }

    private function getChannel(): AMQPChannel
    {
        if (!$this->channel || !$this->connection?->isConnected()) {
            $this->connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password, $this->vhost);
            $this->channel = $this->connection->channel();
        }
        return $this->channel;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
