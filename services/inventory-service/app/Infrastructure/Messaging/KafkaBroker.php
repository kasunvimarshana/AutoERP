<?php
namespace App\Infrastructure\Messaging;
use App\Domain\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;

/**
 * Kafka broker implementation using rdkafka extension.
 * Falls back gracefully if rdkafka is not installed.
 */
class KafkaBroker implements MessageBrokerInterface
{
    private mixed $producer  = null;
    private mixed $consumer  = null;
    private bool  $connected = false;

    public function publish(string $topic, string $event, array $payload, array $options = []): bool
    {
        try {
            $producer = $this->getProducer();
            $kafkaTopic = $producer->newTopic($topic);
            $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $partition = $options['partition'] ?? RD_KAFKA_PARTITION_UA;
            $kafkaTopic->produce($partition, 0, $body, $payload['tenant_id'] ?? null);
            $producer->poll(0);
            $producer->flush(10000);
            return true;
        } catch (\Throwable $e) {
            Log::error("KafkaBroker publish failed: " . $e->getMessage());
            return false;
        }
    }

    public function publishBatch(string $topic, array $messages, array $options = []): bool
    {
        try {
            $producer   = $this->getProducer();
            $kafkaTopic = $producer->newTopic($topic);
            foreach ($messages as $msg) {
                $body = json_encode($msg['payload'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                $kafkaTopic->produce(RD_KAFKA_PARTITION_UA, 0, $body);
            }
            $producer->poll(0);
            $producer->flush(30000);
            return true;
        } catch (\Throwable $e) {
            Log::error("KafkaBroker batch publish failed: " . $e->getMessage());
            return false;
        }
    }

    public function subscribe(string $topic, callable $callback, array $options = []): void
    {
        $consumer = $this->getConsumer();
        $consumer->subscribe([$topic]);
        while (true) {
            $message = $consumer->consume(120 * 1000);
            if ($message === null) continue;
            if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
                $payload = json_decode($message->payload, true);
                $callback($payload, $message);
            } elseif ($message->err !== RD_KAFKA_RESP_ERR__PARTITION_EOF && $message->err !== RD_KAFKA_RESP_ERR__TIMED_OUT) {
                Log::error("Kafka consume error: " . $message->errstr());
            }
        }
    }

    public function acknowledge(mixed $message): void
    {
        $this->consumer?->commitAsync($message);
    }

    public function nack(mixed $message, bool $requeue = false): void
    {
        Log::warning("KafkaBroker nack: message not re-queued (Kafka does not support nack).");
    }

    public function isConnected(): bool { return $this->connected; }

    public function disconnect(): void
    {
        $this->producer = null;
        $this->consumer = null;
        $this->connected = false;
    }

    private function getProducer(): mixed
    {
        if ($this->producer) return $this->producer;
        if (!class_exists('RdKafka\Producer')) throw new \RuntimeException('rdkafka PHP extension is not installed.');
        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', config('queue.connections.kafka.brokers', 'kafka:9092'));
        $conf->set('security.protocol', config('queue.connections.kafka.security_protocol', 'PLAINTEXT'));
        $this->producer  = new \RdKafka\Producer($conf);
        $this->connected = true;
        return $this->producer;
    }

    private function getConsumer(): mixed
    {
        if ($this->consumer) return $this->consumer;
        if (!class_exists('RdKafka\KafkaConsumer')) throw new \RuntimeException('rdkafka PHP extension is not installed.');
        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', config('queue.connections.kafka.brokers', 'kafka:9092'));
        $conf->set('group.id', config('queue.connections.kafka.consumer_group', 'inventory-service'));
        $conf->set('auto.offset.reset', 'earliest');
        $conf->set('enable.auto.commit', 'false');
        $this->consumer  = new \RdKafka\KafkaConsumer($conf);
        $this->connected = true;
        return $this->consumer;
    }
}
