<?php

namespace App\MessageBroker;

use App\MessageBroker\Contracts\MessageBrokerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

class RabbitMQBroker implements MessageBrokerInterface
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public function __construct(
        private readonly string $host,
        private readonly int    $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $vhost = '/',
    ) {
    }

    private function connect(): void
    {
        if ($this->connection !== null && $this->connection->isConnected()) {
            return;
        }

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
        $this->connect();

        $this->channel->exchange_declare(
            $exchange,
            'topic',
            false,
            true,
            false,
        );

        $body = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $amqpMessage = new AMQPMessage($body, [
            'content_type'  => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        $this->channel->basic_publish($amqpMessage, $exchange, $routingKey);

        Log::info('RabbitMQBroker::publish', [
            'exchange'    => $exchange,
            'routing_key' => $routingKey,
        ]);
    }

    public function subscribe(string $queue, callable $handler): void
    {
        $this->connect();

        $this->channel->queue_declare($queue, false, true, false, false);

        $callback = static function (AMQPMessage $message) use ($handler): void {
            $data = json_decode($message->body, true) ?? [];
            $handler($data);
            $message->ack();
        };

        $this->channel->basic_consume($queue, '', false, false, false, false, $callback);

        Log::info('RabbitMQBroker::subscribe', ['queue' => $queue]);

        while ($this->channel->is_open()) {
            $this->channel->wait();
        }
    }

    public function disconnect(): void
    {
        if ($this->channel !== null) {
            try {
                $this->channel->close();
            } catch (\Throwable) {
            }
        }

        if ($this->connection !== null) {
            try {
                $this->connection->close();
            } catch (\Throwable) {
            }
        }

        $this->channel    = null;
        $this->connection = null;

        Log::info('RabbitMQBroker::disconnect');
    }
}
