<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class MessageBrokerService
{
    private ?AMQPStreamConnection $connection = null;

    private ?\PhpAmqpLib\Channel\AMQPChannel $channel = null;

    public function publish(string $routingKey, array $payload): void
    {
        try {
            $this->connect();

            $exchange = config('services.rabbitmq.exchange', 'saas_events');

            $message = new AMQPMessage(
                json_encode($payload),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'timestamp' => time(),
                    'message_id' => \Illuminate\Support\Str::uuid()->toString(),
                ]
            );

            $this->channel->basic_publish($message, $exchange, $routingKey);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('MessageBrokerService: Failed to publish message', [
                'routing_key' => $routingKey,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function connect(): void
    {
        if ($this->connection && $this->connection->isConnected()) {
            return;
        }

        $config = config('services.rabbitmq');

        $this->connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost'],
        );

        $this->channel = $this->connection->channel();

        $this->channel->exchange_declare(
            $config['exchange'],
            'topic',
            false,
            true,
            false
        );
    }

    public function __destruct()
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (\Exception) {
            // ignore
        }
    }
}
