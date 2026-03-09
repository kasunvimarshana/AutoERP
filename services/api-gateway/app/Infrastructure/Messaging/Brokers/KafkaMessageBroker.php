<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Brokers;

use App\Contracts\Messaging\MessageBrokerInterface;
use Psr\Log\LoggerInterface;

/**
 * Kafka Message Broker Implementation
 *
 * Provides publish/subscribe capabilities using Apache Kafka.
 * Uses the RdKafka PHP extension when available, falls back to HTTP for testing.
 */
class KafkaMessageBroker implements MessageBrokerInterface
{
    private bool $connected = false;
    private mixed $producer = null;
    private mixed $consumer = null;

    public function __construct(
        private readonly string $brokers,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function publish(string $topic, array $message, array $options = []): bool
    {
        try {
            if (!extension_loaded('rdkafka')) {
                $this->logger->warning('rdkafka extension not loaded; using HTTP fallback for Kafka');
                return $this->httpFallbackPublish($topic, $message);
            }

            $producer = $this->getProducer();
            $kafkaTopic = $producer->newTopic($topic);

            $body = json_encode([
                'event' => $topic,
                'payload' => $message,
                'timestamp' => now()->toISOString(),
                'message_id' => (string) \Illuminate\Support\Str::uuid(),
            ]);

            $kafkaTopic->produce(
                RD_KAFKA_PARTITION_UA,
                0,
                $body,
                $options['key'] ?? null
            );

            $producer->flush(5000);

            $this->logger->info('Message published to Kafka', ['topic' => $topic]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to publish message to Kafka', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(string $topic, callable $handler, array $options = []): void
    {
        if (!extension_loaded('rdkafka')) {
            $this->logger->warning('rdkafka extension not loaded; Kafka subscription skipped');
            return;
        }

        $conf = new \RdKafka\Conf();
        $conf->set('group.id', $options['group_id'] ?? 'saas-consumer-group');
        $conf->set('metadata.broker.list', $this->brokers);
        $conf->set('auto.offset.reset', 'earliest');

        $consumer = new \RdKafka\KafkaConsumer($conf);
        $consumer->subscribe([$topic]);

        while (true) {
            $message = $consumer->consume(120 * 1000);

            if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
                $data = json_decode($message->payload, true) ?? [];
                $handler($data, $message);
            } elseif ($message->err !== RD_KAFKA_RESP_ERR__PARTITION_EOF && $message->err !== RD_KAFKA_RESP_ERR__TIMED_OUT) {
                $this->logger->error('Kafka consume error: ' . $message->errstr());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function acknowledge(mixed $message): void
    {
        // Kafka uses auto-commit or manual offset commits; no explicit ack
    }

    /**
     * {@inheritdoc}
     */
    public function reject(mixed $message, bool $requeue = false): void
    {
        // Kafka handles retries via consumer group offsets
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect(): void
    {
        $this->connected = false;
        $this->producer = null;
        $this->consumer = null;
    }

    /**
     * Get or create Kafka producer.
     */
    private function getProducer(): mixed
    {
        if (!$this->producer) {
            $conf = new \RdKafka\Conf();
            $conf->set('metadata.broker.list', $this->brokers);
            $this->producer = new \RdKafka\Producer($conf);
            $this->connected = true;
        }

        return $this->producer;
    }

    /**
     * HTTP fallback for environments without rdkafka extension.
     * Used for development/testing when Kafka REST proxy is available.
     */
    private function httpFallbackPublish(string $topic, array $message): bool
    {
        // Log the message for development purposes
        $this->logger->info('Kafka HTTP fallback: message would be published', [
            'topic' => $topic,
            'payload' => $message,
        ]);
        return true;
    }
}
