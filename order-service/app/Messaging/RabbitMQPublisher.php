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
                false,
                'AMQPLAIN',
                null,
                'en_US',
                3.0,
                3.0,
                null,
                false,
                60
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

        // Declare exchanges
        $this->channel->exchange_declare($commandsExchange, 'direct', false, true, false);
        $this->channel->exchange_declare($repliesExchange, 'direct', false, true, false);

        // Declare and bind queues
        $queues = config('rabbitmq.queues');
        foreach ($queues as $key => $queueName) {
            $this->channel->queue_declare($queueName, false, true, false, false);

            if ($key === 'saga_replies') {
                $this->channel->queue_bind($queueName, $repliesExchange, $queueName);
            } else {
                $this->channel->queue_bind($queueName, $commandsExchange, $queueName);
            }
        }

        $this->declared = true;

        Log::info('[RabbitMQ Publisher] Exchanges and queues declared');
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
                'message_id'    => $message['saga_id'] ?? uniqid('msg_', true),
            ]);

            $this->channel->basic_publish($amqpMessage, $exchange, $routingKey);

            Log::debug('[RabbitMQ Publisher] Message published', [
                'exchange'    => $exchange,
                'routing_key' => $routingKey,
                'saga_id'     => $message['saga_id'] ?? 'unknown',
            ]);
        } catch (\Throwable $e) {
            Log::error('[RabbitMQ Publisher] Failed to publish message', [
                'exchange'    => $exchange,
                'routing_key' => $routingKey,
                'error'       => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function isConnected(): bool
    {
        return $this->connection !== null
            && $this->connection->isConnected()
            && $this->channel !== null
            && $this->channel->is_open();
    }

    public function __destruct()
    {
        try {
            if ($this->channel && $this->channel->is_open()) {
                $this->channel->close();
            }
            if ($this->connection && $this->connection->isConnected()) {
                $this->connection->close();
            }
        } catch (\Throwable) {
            // Suppress destructor exceptions
        }
    }
}
