<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Brokers;

use App\Infrastructure\Messaging\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;

class KafkaBroker implements MessageBrokerInterface
{
    public function __construct(private readonly string $brokers) {}

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
            $producer = new \RdKafka\Producer($conf);
            $kafkaTopic = $producer->newTopic($topic);
            $kafkaTopic->produce(\RD_KAFKA_PARTITION_UA, 0, json_encode($message));
            $producer->poll(0);
            for ($i = 0; $i < 3; $i++) {
                if (\RD_KAFKA_RESP_ERR_NO_ERROR === $producer->flush(10000)) {
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
            return;
        }
        $conf = new \RdKafka\Conf();
        $conf->set('group.id', $options['group_id'] ?? 'saga-orchestrator');
        $conf->set('metadata.broker.list', $this->brokers);
        $conf->set('auto.offset.reset', 'earliest');
        $consumer = new \RdKafka\KafkaConsumer($conf);
        $consumer->subscribe([$topic]);
        while (true) {
            $msg = $consumer->consume(120 * 1000);
            if ($msg->err === \RD_KAFKA_RESP_ERR_NO_ERROR) {
                $handler(json_decode($msg->payload, true), $msg);
            }
        }
    }

    public function acknowledge(mixed $message): void {}
    public function reject(mixed $message, bool $requeue = false): void {}

    public function healthCheck(): bool
    {
        return extension_loaded('rdkafka');
    }
}
