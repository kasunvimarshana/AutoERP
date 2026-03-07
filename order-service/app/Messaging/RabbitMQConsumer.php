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
            false,
            'AMQPLAIN',
            null,
            'en_US',
            3.0,
            3.0,
            null,
            false,
            60  // heartbeat=60s to detect dropped connections
        );

        $this->channel = $this->connection->channel();
    }

    /**
     * Begin consuming messages from the given queue.
     *
     * @param  string   $queue
     * @param  callable $callback  Receives the decoded message array. Must return void.
     */
    public function consume(string $queue, callable $callback): void
    {
        // Declare queue (idempotent — safe to call multiple times)
        $this->channel->queue_declare($queue, false, true, false, false);

        // Process one message at a time
        $this->channel->basic_qos(0, 1, false);

        $this->channel->basic_consume(
            $queue,
            '',    // consumer tag
            false, // no local
            false, // no ack (manual ack)
            false, // exclusive
            false, // no wait
            function (AMQPMessage $msg) use ($callback): void {
                $decoded = [];
                try {
                    $decoded = json_decode($msg->getBody(), true, 512, JSON_THROW_ON_ERROR);
                    $callback($decoded);
                    $msg->ack();

                    Log::debug('[RabbitMQ Consumer] Message acknowledged', [
                        'saga_id' => $decoded['saga_id'] ?? 'unknown',
                        'type'    => $decoded['type'] ?? 'unknown',
                    ]);
                } catch (\Throwable $e) {
                    Log::error('[RabbitMQ Consumer] Failed to process message', [
                        'error'   => $e->getMessage(),
                        'saga_id' => $decoded['saga_id'] ?? 'unknown',
                    ]);
                    // Nack without requeue to avoid infinite loops
                    $msg->nack(false);
                }
            }
        );

        Log::info('[RabbitMQ Consumer] Waiting for messages', ['queue' => $queue]);

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function getChannel(): AMQPChannel
    {
        return $this->channel;
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
