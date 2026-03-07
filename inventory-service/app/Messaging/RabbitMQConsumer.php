<?php

namespace App\Messaging;

use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQConsumer
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel          $channel    = null;

    public function __construct()
    {
        $this->connect();
    }

    private function connect(): void
    {
        $this->connection = new AMQPStreamConnection(
            config('rabbitmq.host', 'rabbitmq'),
            config('rabbitmq.port', 5672),
            config('rabbitmq.user', 'guest'),
            config('rabbitmq.password', 'guest'),
            config('rabbitmq.vhost', '/'),
            false, 'AMQPLAIN', null, 'en_US', 3.0, 3.0, null, false, 0
        );
        $this->channel = $this->connection->channel();
    }

    public function getChannel(): AMQPChannel
    {
        return $this->channel;
    }

    public function consume(string $queue, callable $callback): void
    {
        $this->channel->queue_declare($queue, false, true, false, false);
        $this->channel->basic_qos(0, 1, false);

        $this->channel->basic_consume(
            $queue, '', false, false, false, false,
            function (AMQPMessage $msg) use ($callback): void {
                $decoded = [];
                try {
                    $decoded = json_decode($msg->getBody(), true, 512, JSON_THROW_ON_ERROR);
                    $callback($decoded);
                    $msg->ack();
                } catch (\Throwable $e) {
                    Log::error('[RabbitMQ Consumer] Error', ['error' => $e->getMessage()]);
                    $msg->nack(false);
                }
            }
        );

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function __destruct()
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (\Throwable) {}
    }
}
