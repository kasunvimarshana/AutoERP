<?php

declare(strict_types=1);

namespace App\Infrastructure\MessageBroker\Drivers;

use App\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;

/**
 * Kafka message broker driver.
 *
 * Requires the `ext-rdkafka` PHP extension in production.
 * Falls back to structured logging when the extension is absent.
 */
class KafkaMessageBroker implements MessageBrokerInterface
{
    private mixed $producer = null;
    private mixed $consumer = null;

    public function __construct(private readonly array $config)
    {
        $this->boot();
    }

    private function boot(): void
    {
        if (!extension_loaded('rdkafka')) {
            Log::warning('[KafkaMessageBroker] ext-rdkafka not loaded; running in log-only mode.');
            return;
        }

        $kafkaConfig = new \RdKafka\Conf();
        $kafkaConfig->set('metadata.broker.list', $this->config['brokers']);
        $kafkaConfig->set('socket.timeout.ms', (string) ($this->config['socket_timeout_ms'] ?? 60000));

        if (!empty($this->config['sasl_username'])) {
            $kafkaConfig->set('security.protocol', 'SASL_SSL');
            $kafkaConfig->set('sasl.mechanisms', 'PLAIN');
            $kafkaConfig->set('sasl.username', $this->config['sasl_username']);
            $kafkaConfig->set('sasl.password', $this->config['sasl_password']);
        }

        $this->producer = new \RdKafka\Producer($kafkaConfig);
    }

    public function publish(string $topic, array $payload, array $options = []): void
    {
        $message = json_encode([
            'topic'     => $topic,
            'payload'   => $payload,
            'timestamp' => now()->toIso8601String(),
            'id'        => $options['message_id'] ?? \Illuminate\Support\Str::uuid()->toString(),
        ]);

        if ($this->producer === null) {
            Log::info("[Kafka:log-only] topic={$topic}", ['payload' => $payload]);
            return;
        }

        $kafkaTopic = $this->producer->newTopic($topic);
        $kafkaTopic->produce(
            $options['partition'] ?? RD_KAFKA_PARTITION_UA,
            0,
            $message,
            $options['key'] ?? null
        );

        $this->producer->poll(0);
        $this->producer->flush(10000);

        Log::debug("[KafkaMessageBroker] Published to {$topic}.");
    }

    public function subscribe(array $topics, callable $callback, array $options = []): void
    {
        if (!extension_loaded('rdkafka')) {
            Log::warning('[KafkaMessageBroker] subscribe() called but ext-rdkafka is not loaded.');
            return;
        }

        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', $this->config['brokers']);
        $conf->set('group.id', $options['group_id'] ?? $this->config['group_id'] ?? 'inventory-service');
        $conf->set('auto.offset.reset', $options['auto_offset_reset'] ?? 'earliest');

        $consumer = new \RdKafka\KafkaConsumer($conf);
        $consumer->subscribe($topics);

        while (true) {
            $message = $consumer->consume(120 * 1000);

            if ($message === null) {
                continue;
            }

            if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
                $decoded = json_decode($message->payload, true) ?? [];
                $callback($decoded, $message);
            } elseif ($message->err !== RD_KAFKA_RESP_ERR__PARTITION_EOF
                && $message->err !== RD_KAFKA_RESP_ERR__TIMED_OUT) {
                Log::error("[KafkaMessageBroker] Consumer error: {$message->errstr()}");
            }
        }
    }

    public function acknowledge(mixed $messageId): void
    {
        // Kafka uses offset commits; acknowledgement is implicit via offset management.
    }

    public function reject(mixed $messageId, bool $requeue = false): void
    {
        // Kafka does not support individual message rejection; handle via DLQ topic.
        Log::warning("[KafkaMessageBroker] Message rejected (requeue={$requeue}). Consider routing to DLQ.");
    }

    public function isHealthy(): bool
    {
        try {
            if ($this->producer === null) {
                return false;
            }

            $metadata = $this->producer->getMetadata(true, null, 5000);
            return $metadata !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getDriver(): string
    {
        return 'kafka';
    }
}
