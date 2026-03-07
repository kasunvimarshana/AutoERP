<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQService
{
    private ?AMQPStreamConnection $connection = null;

    private function getConnection(): AMQPStreamConnection
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            $this->connection = new AMQPStreamConnection(
                config('rabbitmq.host'),
                config('rabbitmq.port'),
                config('rabbitmq.user'),
                config('rabbitmq.password'),
                config('rabbitmq.vhost')
            );
        }
        return $this->connection;
    }

    public function publish(string $exchange, string $routingKey, array $payload): void
    {
        try {
            $connection = $this->getConnection();
            $channel = $connection->channel();

            $channel->exchange_declare($exchange, 'topic', false, true, false);

            $message = new AMQPMessage(
                json_encode($payload),
                ['content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );

            $channel->basic_publish($message, $exchange, $routingKey);
            $channel->close();

            Log::info("Message published to {$exchange} with key {$routingKey}", ['payload' => $payload]);
        } catch (\Exception $e) {
            Log::error("Failed to publish message: {$e->getMessage()}", [
                'exchange' => $exchange,
                'routing_key' => $routingKey,
            ]);
        }
    }

    public function __destruct()
    {
        if ($this->connection && $this->connection->isConnected()) {
            $this->connection->close();
        }
    }
}
