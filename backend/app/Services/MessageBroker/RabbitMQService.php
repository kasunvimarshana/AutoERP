<?php

namespace App\Services\MessageBroker;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

class RabbitMQService implements MessageBrokerInterface
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $vhost,
    ) {}

    private function getChannel(): AMQPChannel
    {
        if ($this->channel === null || !$this->channel->is_open()) {
            $this->connect();
        }
        return $this->channel;
    }

    private function connect(): void
    {
        $this->connection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->user,
            $this->password,
            $this->vhost,
        );
        $this->channel = $this->connection->channel();
    }

    public function publish(string $exchange, string $routingKey, array $message): void
    {
        try {
            $channel = $this->getChannel();
            $this->declareExchange($exchange);

            $msg = new AMQPMessage(
                json_encode($message),
                [
                    'content_type'  => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                ]
            );

            $channel->basic_publish($msg, $exchange, $routingKey);
            Log::debug("Published to {$exchange}/{$routingKey}", $message);
        } catch (\Throwable $e) {
            Log::error("RabbitMQ publish failed: {$e->getMessage()}", [
                'exchange'    => $exchange,
                'routing_key' => $routingKey,
            ]);
            throw $e;
        }
    }

    public function consume(string $queue, callable $callback): void
    {
        $channel = $this->getChannel();
        $channel->basic_consume($queue, '', false, false, false, false, $callback);
        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    public function declareExchange(string $exchange, string $type = AMQPExchangeType::TOPIC): void
    {
        $this->getChannel()->exchange_declare($exchange, $type, false, true, false);
    }

    public function declareQueue(string $queue, string $exchange, string $routingKey): void
    {
        $channel = $this->getChannel();
        $channel->queue_declare($queue, false, true, false, false);
        $channel->queue_bind($queue, $exchange, $routingKey);
    }

    public function __destruct()
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (\Throwable) {
            // Ignore cleanup errors
        }
    }
}
