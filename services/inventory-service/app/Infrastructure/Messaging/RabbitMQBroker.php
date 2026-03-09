<?php
namespace App\Infrastructure\Messaging;
use App\Domain\Contracts\MessageBrokerInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Illuminate\Support\Facades\Log;

class RabbitMQBroker implements MessageBrokerInterface
{
    private ?AMQPStreamConnection $connection = null;
    private ?object $channel = null;
    private int $retries;

    public function __construct()
    {
        $this->retries = (int) config('queue.connections.rabbitmq.options.max_retries', 3);
    }

    private function connect(): void
    {
        if ($this->connection && $this->connection->isConnected()) return;
        $host = config('queue.connections.rabbitmq.hosts.0', []);
        $attempt = 0;
        while ($attempt < $this->retries) {
            try {
                $this->connection = new AMQPStreamConnection(
                    $host['host'] ?? 'rabbitmq', $host['port'] ?? 5672,
                    $host['user'] ?? 'guest', $host['password'] ?? 'guest',
                    $host['vhost'] ?? '/',
                    false, 'AMQPLAIN', null, 'en_US',
                    (int) env('RABBITMQ_CONNECTION_TIMEOUT', 10),
                    (float) env('RABBITMQ_READ_WRITE_TIMEOUT', 60),
                    null, (bool) env('RABBITMQ_KEEPALIVE', false),
                    (int) env('RABBITMQ_HEARTBEAT', 10)
                );
                $this->channel = $this->connection->channel();
                $exchange = config('queue.connections.rabbitmq.options.queue.exchange', 'inventory_events');
                $exchangeType = config('queue.connections.rabbitmq.options.queue.exchange_type', 'topic');
                $this->channel->exchange_declare($exchange, $exchangeType, false, true, false);
                $dlx = config('queue.connections.rabbitmq.options.queue.failed_exchange', 'inventory_dlx');
                $this->channel->exchange_declare($dlx, 'direct', false, true, false);
                return;
            } catch (\Throwable $e) {
                $attempt++;
                Log::warning("RabbitMQ connect attempt {$attempt} failed: " . $e->getMessage());
                if ($attempt >= $this->retries) throw $e;
                sleep(min(2 ** $attempt, 30));
            }
        }
    }

    public function publish(string $topic, string $event, array $payload, array $options = []): bool
    {
        try {
            $this->connect();
            $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $msg  = new AMQPMessage($body, array_merge([
                'content_type'  => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'app_id'        => config('app.name', 'inventory-service'),
                'timestamp'     => time(),
                'message_id'    => $payload['id'] ?? uniqid(),
                'type'          => $event,
            ], $options['properties'] ?? []));
            if (!empty($options['headers'])) {
                $msg->set('application_headers', new AMQPTable($options['headers']));
            }
            $this->channel->basic_publish($msg, $topic, $event);
            return true;
        } catch (\Throwable $e) {
            Log::error("RabbitMQ publish failed: " . $e->getMessage());
            return false;
        }
    }

    public function publishBatch(string $topic, array $messages, array $options = []): bool
    {
        try {
            $this->connect();
            foreach ($messages as $msg) {
                $body = json_encode($msg['payload'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                $amqpMsg = new AMQPMessage($body, [
                    'content_type'  => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'type'          => $msg['event'],
                ]);
                $this->channel->batch_basic_publish($amqpMsg, $topic, $msg['event']);
            }
            $this->channel->publish_batch();
            return true;
        } catch (\Throwable $e) {
            Log::error("RabbitMQ batch publish failed: " . $e->getMessage());
            return false;
        }
    }

    public function subscribe(string $topic, callable $callback, array $options = []): void
    {
        $this->connect();
        $queue = $options['queue'] ?? config('queue.connections.rabbitmq.queue', 'inventory_queue');
        $exchange = $topic;
        $routingKey = $options['routing_key'] ?? '#';
        $this->channel->queue_declare($queue, false, true, false, false);
        $this->channel->queue_bind($queue, $exchange, $routingKey);
        $this->channel->basic_qos(null, $options['prefetch'] ?? 10, null);
        $this->channel->basic_consume($queue, '', false, false, false, false, function ($msg) use ($callback) {
            $payload = json_decode($msg->body, true);
            $callback($payload, $msg);
        });
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function acknowledge(mixed $message): void
    {
        $message->ack();
    }

    public function nack(mixed $message, bool $requeue = false): void
    {
        $message->nack($requeue);
    }

    public function isConnected(): bool
    {
        return $this->connection?->isConnected() ?? false;
    }

    public function disconnect(): void
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (\Throwable) {}
        $this->channel    = null;
        $this->connection = null;
    }

    public function __destruct() { $this->disconnect(); }
}
