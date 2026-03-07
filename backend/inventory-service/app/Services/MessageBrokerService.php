<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
            $msg = new AMQPMessage(json_encode($payload), [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'message_id' => Str::uuid()->toString(),
                'timestamp' => time(),
            ]);
            $this->channel->basic_publish($msg, $exchange, $routingKey);
        } catch (\Exception $e) {
            Log::error('MessageBrokerService publish failed', ['key' => $routingKey, 'error' => $e->getMessage()]);
        }
    }

    private function connect(): void
    {
        if ($this->connection && $this->connection->isConnected()) {
            return;
        }
        $c = config('services.rabbitmq');
        $this->connection = new AMQPStreamConnection($c['host'], $c['port'], $c['user'], $c['password'], $c['vhost']);
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare($c['exchange'], 'topic', false, true, false);
    }

    public function __destruct()
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (\Exception $e) {
            // Suppress cleanup errors on shutdown; connection may already be closed
        }
    }
}
