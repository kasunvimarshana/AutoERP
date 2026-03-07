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
                config('rabbitmq.host'), config('rabbitmq.port'),
                config('rabbitmq.user'), config('rabbitmq.password'), config('rabbitmq.vhost')
            );
        }
        return $this->connection;
    }

    public function publish(string $exchange, string $routingKey, array $payload): void
    {
        try {
            $channel = $this->getConnection()->channel();
            $channel->exchange_declare($exchange, 'topic', false, true, false);
            $msg = new AMQPMessage(
                json_encode($payload),
                ['content_type' => 'application/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );
            $channel->basic_publish($msg, $exchange, $routingKey);
            $channel->close();
            Log::info("Published to {$exchange}/{$routingKey}");
        } catch (\Exception $e) {
            Log::error("Publish failed: {$e->getMessage()}");
        }
    }

    public function __destruct()
    {
        if ($this->connection && $this->connection->isConnected()) {
            $this->connection->close();
        }
    }
}
