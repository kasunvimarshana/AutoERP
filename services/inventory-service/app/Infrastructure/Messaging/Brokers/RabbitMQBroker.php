<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Brokers;

use App\Infrastructure\Messaging\Contracts\MessageBrokerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * RabbitMQ Message Broker Implementation
 *
 * Implements the MessageBrokerInterface using php-amqplib.
 */
class RabbitMQBroker implements MessageBrokerInterface
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $vhost = '/'
    ) {}

    public function publish(string $topic, array $message, array $options = []): bool
    {
        try {
            $channel = $this->getChannel();
            $channel->exchange_declare($topic, 'fanout', false, true, false);

            $msg = new AMQPMessage(
                json_encode($message),
                ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
            );

            $channel->basic_publish($msg, $topic);
            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    public function subscribe(string $topic, callable $handler, array $options = []): void
    {
        $channel = $this->getChannel();
        $queueName = $options['queue'] ?? $topic . '_queue';

        $channel->exchange_declare($topic, 'fanout', false, true, false);
        $channel->queue_declare($queueName, false, true, false, false);
        $channel->queue_bind($queueName, $topic);

        $channel->basic_consume(
            $queueName,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $msg) use ($handler) {
                $data = json_decode($msg->body, true);
                $handler($data, $msg);
            }
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    public function acknowledge(mixed $message): void
    {
        if ($message instanceof AMQPMessage) {
            $message->ack();
        }
    }

    public function reject(mixed $message, bool $requeue = false): void
    {
        if ($message instanceof AMQPMessage) {
            $message->nack($requeue);
        }
    }

    public function healthCheck(): bool
    {
        try {
            $conn = $this->getConnection();
            return $conn->isConnected();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getConnection(): AMQPStreamConnection
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            $this->connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->password,
                $this->vhost
            );
        }
        return $this->connection;
    }

    private function getChannel(): AMQPChannel
    {
        if ($this->channel === null) {
            $this->channel = $this->getConnection()->channel();
        }
        return $this->channel;
    }
}
