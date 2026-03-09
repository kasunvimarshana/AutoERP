<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Brokers;

use App\Infrastructure\Messaging\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;

/**
 * Kafka Message Broker Implementation
 *
 * Implements MessageBrokerInterface for Apache Kafka.
 * Uses rdkafka PHP extension when available.
 */
class KafkaBroker implements MessageBrokerInterface
{
    public function __construct(
        private readonly string $brokers
    ) {}

    public function publish(string $topic, array $message, array $options = []): bool
    {
        try {
            if (!extension_loaded('rdkafka')) {
                Log::warning('rdkafka extension not loaded; falling back to log');
                Log::info("[KAFKA] Topic: {$topic}", $message);
                return true;
            }

            $conf = new \RdKafka\Conf();
            $conf->set('metadata.broker.list', $this->brokers);
            $conf->set('socket.timeout.ms', '5000');

            $producer = new \RdKafka\Producer($conf);
            $kafkaTopic = $producer->newTopic($topic);
            $kafkaTopic->produce(\RD_KAFKA_PARTITION_UA, 0, json_encode($message));
            $producer->poll(0);

            for ($flushRetries = 0; $flushRetries < 3; $flushRetries++) {
                $result = $producer->flush(10000);
                if (\RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    public function subscribe(string $topic, callable $handler, array $options = []): void
    {
        if (!extension_loaded('rdkafka')) {
            Log::warning('rdkafka extension not loaded; cannot subscribe');
            return;
        }

        $conf = new \RdKafka\Conf();
        $conf->set('group.id', $options['group_id'] ?? 'inventory-service');
        $conf->set('metadata.broker.list', $this->brokers);
        $conf->set('auto.offset.reset', 'earliest');

        $consumer = new \RdKafka\KafkaConsumer($conf);
        $consumer->subscribe([$topic]);

        while (true) {
            $msg = $consumer->consume(120 * 1000);

            if ($msg->err === \RD_KAFKA_RESP_ERR_NO_ERROR) {
                $data = json_decode($msg->payload, true);
                $handler($data, $msg);
            }
        }
    }

    public function acknowledge(mixed $message): void
    {
        // Kafka uses offset commits; no explicit ack needed with auto-commit
    }

    public function reject(mixed $message, bool $requeue = false): void
    {
        // Kafka doesn't support individual message rejection
        // Dead-letter queue patterns handled at application level
    }

    public function healthCheck(): bool
    {
        try {
            if (!extension_loaded('rdkafka')) {
                return false;
            }

            $conf = new \RdKafka\Conf();
            $conf->set('metadata.broker.list', $this->brokers);
            $conf->set('socket.timeout.ms', '3000');

            $producer = new \RdKafka\Producer($conf);
            $metadata = $producer->getMetadata(true, null, 3000);
            return count($metadata->getBrokers()) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
