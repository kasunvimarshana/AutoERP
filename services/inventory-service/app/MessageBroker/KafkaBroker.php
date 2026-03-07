<?php

namespace App\MessageBroker;

use App\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;

class KafkaBroker implements MessageBrokerInterface
{
    private ?\RdKafka\Producer  $producer  = null;
    private ?\RdKafka\KafkaConsumer $consumer = null;

    public function __construct(
        private readonly string $brokers = 'kafka:9092',
        private readonly string $groupId = 'inventory-service',
    ) {}

    // -------------------------------------------------------------------------
    // MessageBrokerInterface
    // -------------------------------------------------------------------------

    public function publish(string $topic, array $payload): bool
    {
        if (! class_exists(\RdKafka\Producer::class)) {
            Log::warning('KafkaBroker: ext-rdkafka is not installed. Message dropped.', ['topic' => $topic]);

            return false;
        }

        try {
            $producer = $this->getProducer();
            $kafkaTopic = $producer->newTopic($topic);

            $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $kafkaTopic->produce(RD_KAFKA_PARTITION_UA, 0, $body);
            $producer->poll(0);

            // Flush with a 10-second timeout
            $result = $producer->flush(10000);

            if ($result !== RD_KAFKA_RESP_ERR_NO_ERROR) {
                Log::error('KafkaBroker: flush failed', ['topic' => $topic, 'error_code' => $result]);

                return false;
            }

            Log::debug('Kafka message published', ['topic' => $topic]);

            return true;
        } catch (\Throwable $e) {
            Log::error('KafkaBroker publish error', ['topic' => $topic, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function subscribe(string $topic, callable $callback): void
    {
        if (! class_exists(\RdKafka\KafkaConsumer::class)) {
            Log::warning('KafkaBroker: ext-rdkafka is not installed. Cannot subscribe.', ['topic' => $topic]);

            return;
        }

        try {
            $conf = new \RdKafka\Conf();
            $conf->set('group.id', $this->groupId);
            $conf->set('metadata.broker.list', $this->brokers);
            $conf->set('auto.offset.reset', 'earliest');
            $conf->set('enable.auto.commit', 'true');

            $consumer = new \RdKafka\KafkaConsumer($conf);
            $consumer->subscribe([$topic]);

            Log::debug('Kafka subscribed', ['topic' => $topic, 'group_id' => $this->groupId]);

            while (true) {
                $message = $consumer->consume(10000);

                if ($message === null) {
                    continue;
                }

                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        try {
                            $payload = json_decode($message->payload, true, 512, JSON_THROW_ON_ERROR);
                            $callback($payload);
                        } catch (\Throwable $e) {
                            Log::error('Kafka consumer callback error', ['error' => $e->getMessage()]);
                        }
                        break;

                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        // No messages yet; continue polling
                        break;

                    default:
                        Log::error('Kafka consumer error', [
                            'topic' => $topic,
                            'error' => $message->errstr(),
                        ]);
                        break;
                }
            }
        } catch (\Throwable $e) {
            Log::error('KafkaBroker subscribe error', ['topic' => $topic, 'error' => $e->getMessage()]);
        }
    }

    public function disconnect(): void
    {
        $this->producer = null;
        $this->consumer = null;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function getProducer(): \RdKafka\Producer
    {
        if ($this->producer === null) {
            $conf = new \RdKafka\Conf();
            $conf->set('metadata.broker.list', $this->brokers);
            $this->producer = new \RdKafka\Producer($conf);
        }

        return $this->producer;
    }
}
