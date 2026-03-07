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
    private ?AMQPChannel          $channel    = null;

    public function __construct(
        private readonly string $host     = 'rabbitmq',
        private readonly int    $port     = 5672,
        private readonly string $user     = 'guest',
        private readonly string $password = 'guest',
        private readonly string $vhost    = '/',
        private readonly string $exchange = 'saas.events',
    ) {}

    // -------------------------------------------------------------------------
    // Connection management
    // -------------------------------------------------------------------------

    private function connect(): void
    {
        if ($this->connection && $this->connection->isConnected()) {
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

        // Declare a topic exchange so routing-key patterns work
        $this->channel->exchange_declare(
            exchange:    $this->exchange,
            type:        'topic',
            passive:     false,
            durable:     true,
            auto_delete: false,
        );

        Log::debug('RabbitMQ connected', ['host' => $this->host, 'exchange' => $this->exchange]);
    }

    private function ensureChannel(): AMQPChannel
    {
        $this->connect();

        return $this->channel;
    }

    // -------------------------------------------------------------------------
    // MessageBrokerInterface
    // -------------------------------------------------------------------------

    public function publish(string $topic, array $payload): bool
    {
        try {
            $channel = $this->ensureChannel();

            $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

            $msg = new AMQPMessage($body, [
                'content_type'  => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'timestamp'     => time(),
                'app_id'        => 'product-service',
            ]);

            $channel->basic_publish($msg, $this->exchange, $topic);

            Log::debug('RabbitMQ message published', ['topic' => $topic]);

            return true;
        } catch (\Throwable $e) {
            Log::error('RabbitMQ publish error', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function subscribe(string $topic, callable $callback): void
    {
        try {
            $channel = $this->ensureChannel();

            // Create an exclusive, auto-delete queue bound to the routing-key pattern
            [$queue] = $channel->queue_declare('', false, false, true, true);

            $channel->queue_bind($queue, $this->exchange, $topic);

            $channel->basic_qos(null, 1, null);

            $channel->basic_consume(
                queue:        $queue,
                consumer_tag: '',
                no_local:     false,
                no_ack:       false,
                exclusive:    false,
                nowait:       false,
                callback:     function (AMQPMessage $msg) use ($callback) {
                    try {
                        $payload = json_decode($msg->body, true, 512, JSON_THROW_ON_ERROR);
                        $callback($payload);
                        $msg->ack();
                    } catch (\Throwable $e) {
                        Log::error('RabbitMQ consumer error', ['error' => $e->getMessage()]);
                        $msg->nack(requeue: false);
                    }
                }
            );

            // Block and process messages
            while ($channel->is_consuming()) {
                $channel->wait();
            }
        } catch (\Throwable $e) {
            Log::error('RabbitMQ subscribe error', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function disconnect(): void
    {
        try {
            if ($this->channel) {
                $this->channel->close();
                $this->channel = null;
            }

            if ($this->connection && $this->connection->isConnected()) {
                $this->connection->close();
                $this->connection = null;
            }

            Log::debug('RabbitMQ disconnected');
        } catch (\Throwable $e) {
            Log::warning('RabbitMQ disconnect error', ['error' => $e->getMessage()]);
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
