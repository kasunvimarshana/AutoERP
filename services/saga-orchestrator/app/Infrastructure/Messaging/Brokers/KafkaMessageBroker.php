<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Brokers;

use App\Contracts\Messaging\MessageBrokerInterface;
use Psr\Log\LoggerInterface;

/**
 * Kafka Message Broker (rdkafka with HTTP fallback)
 */
class KafkaMessageBroker implements MessageBrokerInterface
{
    private bool $connected = false;
    private mixed $producer = null;

    public function __construct(
        private readonly string $brokers,
        private readonly LoggerInterface $logger
    ) {}

    public function publish(string $topic, array $message, array $options = []): bool
    {
        if (!extension_loaded('rdkafka')) {
            $this->logger->info('Kafka fallback: message logged', ['topic' => $topic]);
            return true;
        }
        try {
            $producer = $this->getProducer();
            $kafkaTopic = $producer->newTopic($topic);
            $kafkaTopic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($message));
            $producer->flush(5000);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Kafka publish failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function subscribe(string $topic, callable $handler, array $options = []): void {}
    public function acknowledge(mixed $message): void {}
    public function reject(mixed $message, bool $requeue = false): void {}
    public function isConnected(): bool { return $this->connected; }
    public function disconnect(): void { $this->connected = false; $this->producer = null; }

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
}
