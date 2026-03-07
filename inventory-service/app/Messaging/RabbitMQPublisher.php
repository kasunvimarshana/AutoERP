<?php

namespace App\Messaging;

use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQPublisher
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel          $channel    = null;
    private bool                  $declared   = false;

    public function __construct()
    {
        $this->connect();
    }

    private function connect(): void
    {
        try {
            $this->connection = new AMQPStreamConnection(
                config('rabbitmq.host', 'rabbitmq'),
                config('rabbitmq.port', 5672),
                config('rabbitmq.user', 'guest'),
                config('rabbitmq.password', 'guest'),
                config('rabbitmq.vhost', '/'),
                false, 'AMQPLAIN', null, 'en_US', 3.0, 3.0, null, false, 60
            );
            $this->channel = $this->connection->channel();
            $this->declareExchangesAndQueues();
        } catch (\Throwable $e) {
            Log::error('[RabbitMQ Publisher] Connection failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function declareExchangesAndQueues(): void
    {
        if ($this->declared) {
            return;
        }
        $commandsExchange = config('rabbitmq.exchanges.commands');
        $repliesExchange  = config('rabbitmq.exchanges.replies');

        $this->channel->exchange_declare($commandsExchange, 'direct', false, true, false);
        $this->channel->exchange_declare($repliesExchange, 'direct', false, true, false);

        foreach (config('rabbitmq.queues') as $key => $queueName) {
            $this->channel->queue_declare($queueName, false, true, false, false);
            $exchange = ($key === 'saga_replies') ? $repliesExchange : $commandsExchange;
            $this->channel->queue_bind($queueName, $exchange, $queueName);
        }
        $this->declared = true;
    }

    public function publish(string $exchange, string $routingKey, array $message): void
    {
        try {
            if (! $this->isConnected()) {
                $this->connect();
            }

            $body = json_encode($message, JSON_THROW_ON_ERROR);
            $amqpMessage = new AMQPMessage($body, [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_type'  => 'application/json',
                'timestamp'     => time(),
            ]);

            $this->channel->basic_publish($amqpMessage, $exchange, $routingKey);

            Log::debug('[RabbitMQ Publisher] Message published', [
                'exchange'    => $exchange,
                'routing_key' => $routingKey,
            ]);
        } catch (\Throwable $e) {
            Log::error('[RabbitMQ Publisher] Publish failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function isConnected(): bool
    {
        return $this->connection?->isConnected() && $this->channel?->is_open();
    }

    public function __destruct()
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (\Throwable) {}
    }
}
