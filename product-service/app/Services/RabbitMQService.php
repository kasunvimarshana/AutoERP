<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * RabbitMQService
 *
 * Handles publishing messages to RabbitMQ for cross-service
 * event-driven communication. Other services (Node.js, Python, Java, etc.)
 * subscribe to the exchange to receive product events.
 *
 * Exchange Type: topic
 * - product.created  -> consumed by Inventory Service, Order Service, etc.
 * - product.updated  -> consumed by Inventory Service, Catalog Service, etc.
 * - product.deleted  -> consumed by Inventory Service (cascade delete), etc.
 */
class RabbitMQService
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    private string $host;
    private int $port;
    private string $user;
    private string $password;
    private string $vhost;
    private string $exchange;

    public function __construct()
    {
        $this->host     = config('rabbitmq.host', 'rabbitmq');
        $this->port     = (int) config('rabbitmq.port', 5672);
        $this->user     = config('rabbitmq.user', 'guest');
        $this->password = config('rabbitmq.password', 'guest');
        $this->vhost    = config('rabbitmq.vhost', '/');
        $this->exchange = config('rabbitmq.exchange', 'product_events');
    }

    /**
     * Publish an event payload to the RabbitMQ exchange.
     *
     * @param  string $routingKey  e.g. "product.created"
     * @param  array  $payload     The message body (will be JSON-encoded)
     * @throws \Exception
     */
    public function publish(string $routingKey, array $payload): void
    {
        try {
            $this->connect();

            $messageBody = json_encode($payload, JSON_THROW_ON_ERROR);

            $message = new AMQPMessage($messageBody, [
                'content_type'  => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'timestamp'     => time(),
                'app_id'        => 'product-service',
            ]);

            $this->channel->basic_publish(
                $message,
                $this->exchange,
                $routingKey
            );

            Log::info('RabbitMQ: Published event', [
                'routing_key' => $routingKey,
                'payload'     => $payload,
            ]);
        } catch (\Exception $e) {
            Log::error('RabbitMQ: Failed to publish event', [
                'routing_key' => $routingKey,
                'error'       => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            $this->close();
        }
    }

    /**
     * Establish connection and declare the exchange.
     */
    private function connect(): void
    {
        $this->connection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->user,
            $this->password,
            $this->vhost
        );

        $this->channel = $this->connection->channel();

        // Declare a topic exchange (durable, so it survives RabbitMQ restarts)
        $this->channel->exchange_declare(
            $this->exchange,
            'topic',        // type: topic allows wildcard routing keys
            false,          // passive
            true,           // durable
            false           // auto_delete
        );
    }

    /**
     * Close the channel and connection.
     */
    private function close(): void
    {
        try {
            if ($this->channel) {
                $this->channel->close();
                $this->channel = null;
            }
            if ($this->connection) {
                $this->connection->close();
                $this->connection = null;
            }
        } catch (\Exception $e) {
            Log::warning('RabbitMQ: Error closing connection', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
