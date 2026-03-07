<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Throwable;

/**
 * RabbitMQService
 *
 * Handles publishing commands and events to RabbitMQ, and subscribing to queues
 * for incoming replies.  Connection is established lazily and retried on failure.
 *
 * Exchange topology (matches infrastructure/rabbitmq/definitions.json):
 *   saga.commands  – direct exchange for service-specific commands
 *   saga.events    – topic exchange for domain events
 */
class RabbitMQService
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel          $channel    = null;

    private const MAX_RETRIES     = 5;
    private const RETRY_DELAY_MS  = 2_000;

    // -------------------------------------------------------------------------
    // Connection management
    // -------------------------------------------------------------------------

    /**
     * Return (or establish) the AMQP channel, retrying on failure.
     */
    public function getChannel(): AMQPChannel
    {
        if ($this->channel !== null && $this->channel->is_open()) {
            return $this->channel;
        }

        $this->connect();

        return $this->channel;
    }

    /**
     * Connect to RabbitMQ with exponential back-off.
     */
    public function connect(): void
    {
        $attempt = 0;

        while ($attempt < self::MAX_RETRIES) {
            try {
                $this->connection = new AMQPStreamConnection(
                    host:         config('rabbitmq.host', 'rabbitmq'),
                    port:         (int) config('rabbitmq.port', 5672),
                    user:         config('rabbitmq.user', 'saga_user'),
                    password:     config('rabbitmq.password', 'saga_password'),
                    vhost:        config('rabbitmq.vhost', '/'),
                    read_write_timeout: 60,
                    heartbeat:    30,
                    connection_timeout: 10,
                );

                $this->channel = $this->connection->channel();

                Log::info('[RabbitMQ] Connected successfully.');

                return;
            } catch (Throwable $e) {
                $attempt++;
                $delay = self::RETRY_DELAY_MS * $attempt;

                Log::warning("[RabbitMQ] Connection attempt {$attempt} failed. Retrying in {$delay}ms.", [
                    'error' => $e->getMessage(),
                ]);

                if ($attempt >= self::MAX_RETRIES) {
                    throw new \RuntimeException(
                        "RabbitMQ connection failed after {$attempt} attempts: {$e->getMessage()}",
                        0,
                        $e
                    );
                }

                usleep($delay * 1_000);
            }
        }
    }

    /**
     * Gracefully close the connection.
     */
    public function disconnect(): void
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (Throwable) {
            // Best effort.
        } finally {
            $this->channel    = null;
            $this->connection = null;
        }
    }

    // -------------------------------------------------------------------------
    // Publishing
    // -------------------------------------------------------------------------

    /**
     * Publish a message to an exchange with a routing key.
     *
     * @param  array<string, mixed>  $payload
     */
    public function publish(string $exchange, string $routingKey, array $payload): void
    {
        $channel = $this->getChannel();

        $body    = json_encode($payload, JSON_THROW_ON_ERROR);
        $headers = new AMQPTable([
            'content-type'  => 'application/json',
            'timestamp'     => time(),
            'message-id'    => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $message = new AMQPMessage($body, [
            'delivery_mode'      => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'content_type'       => 'application/json',
            'application_headers' => $headers,
        ]);

        $channel->basic_publish($message, $exchange, $routingKey);

        Log::debug('[RabbitMQ] Published message.', [
            'exchange'    => $exchange,
            'routing_key' => $routingKey,
            'payload'     => $payload,
        ]);
    }

    /**
     * Publish a command to a specific service's command queue.
     *
     * Uses the saga.commands direct exchange with routing key "{service}.commands".
     *
     * @param  array<string, mixed>  $payload
     */
    public function publishCommand(string $service, string $command, array $payload): void
    {
        $enriched = array_merge($payload, [
            'command'    => $command,
            'service'    => $service,
            'issued_at'  => now()->toIso8601String(),
            'message_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $this->publish('saga.commands', "{$service}.commands", $enriched);
    }

    /**
     * Publish a domain event to the saga.events topic exchange.
     *
     * @param  array<string, mixed>  $payload
     */
    public function publishEvent(string $event, array $payload): void
    {
        $enriched = array_merge($payload, [
            'event'       => $event,
            'emitted_at'  => now()->toIso8601String(),
            'message_id'  => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $this->publish('saga.events', $event, $enriched);
    }

    // -------------------------------------------------------------------------
    // Consuming
    // -------------------------------------------------------------------------

    /**
     * Subscribe to a queue and invoke the handler for each message.
     *
     * The handler receives the decoded array payload and the raw AMQPMessage.
     * If the handler returns true (or void), the message is acked.
     * If the handler returns false or throws, the message is nacked and requeued.
     *
     * @param  callable(array, AMQPMessage): bool|void  $handler
     */
    public function subscribe(string $queue, callable $handler): void
    {
        $channel = $this->getChannel();

        $channel->basic_qos(0, 10, false);

        $channel->basic_consume(
            queue:       $queue,
            consumer_tag: '',
            no_local:    false,
            no_ack:      false,
            exclusive:   false,
            nowait:      false,
            callback:    function (AMQPMessage $message) use ($handler): void {
                try {
                    $payload = json_decode($message->body, true, 512, JSON_THROW_ON_ERROR);
                    $result  = $handler($payload, $message);

                    if ($result !== false) {
                        $message->ack();
                    } else {
                        $message->nack(requeue: true);
                    }
                } catch (Throwable $e) {
                    Log::error('[RabbitMQ] Message handler threw exception – nacking.', [
                        'queue' => $queue,
                        'error' => $e->getMessage(),
                        'body'  => $message->body,
                    ]);
                    $message->nack(requeue: true);
                }
            }
        );

        Log::info("[RabbitMQ] Subscribed to queue '{$queue}'.");

        // Block and consume until the channel is closed.
        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
}
