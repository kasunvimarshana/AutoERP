<?php

namespace App\MessageBroker;

use App\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;

/**
 * Kafka broker implementation using the rdkafka PHP extension.
 *
 * Requires: ext-rdkafka (https://github.com/arnaud-lb/php-rdkafka)
 * The extension must be installed separately; this implementation uses the
 * low-level \RdKafka API that the extension exposes.
 */
class KafkaBroker implements MessageBrokerInterface
{
    private ?\RdKafka\Producer      $producer = null;
    private ?\RdKafka\KafkaConsumer $consumer = null;

    public function __construct(
        private readonly string $brokers        = 'kafka:9092',
        private readonly string $groupId        = 'order-service',
        private readonly int    $timeoutMs      = 5000,
        private readonly int    $flushTimeoutMs = 10000,
    ) {}

    // -------------------------------------------------------------------------
    // Producer
    // -------------------------------------------------------------------------

    private function getProducer(): \RdKafka\Producer
    {
        if ($this->producer !== null) {
            return $this->producer;
        }

        $conf = new \RdKafka\Conf();
        $conf->set('bootstrap.servers', $this->brokers);
        $conf->set('socket.timeout.ms', (string) $this->timeoutMs);
        $conf->set('enable.idempotence', 'true');

        $conf->setDrMsgCb(function (\RdKafka\Producer $kafka, ?\RdKafka\Message $message): void {
            if ($message !== null && $message->err !== RD_KAFKA_RESP_ERR_NO_ERROR) {
                Log::error('Kafka delivery error', [
                    'topic' => $message->topic_name,
                    'error' => $message->errstr(),
                ]);
            }
        });

        $this->producer = new \RdKafka\Producer($conf);

        return $this->producer;
    }

    // -------------------------------------------------------------------------
    // Consumer
    // -------------------------------------------------------------------------

    private function getConsumer(string $topic): \RdKafka\KafkaConsumer
    {
        if ($this->consumer !== null) {
            return $this->consumer;
        }

        $conf = new \RdKafka\Conf();
        $conf->set('bootstrap.servers', $this->brokers);
        $conf->set('group.id', $this->groupId);
        $conf->set('auto.offset.reset', 'earliest');
        $conf->set('enable.auto.commit', 'false');

        $this->consumer = new \RdKafka\KafkaConsumer($conf);
        $this->consumer->subscribe([$topic]);

        return $this->consumer;
    }

    // -------------------------------------------------------------------------
    // MessageBrokerInterface
    // -------------------------------------------------------------------------

    public function publish(string $topic, array $payload): bool
    {
        try {
            $producer    = $this->getProducer();
            $kafkaTopic  = $producer->newTopic($topic);
            $messageBody = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

            $kafkaTopic->produce(RD_KAFKA_PARTITION_UA, 0, $messageBody);
            $producer->poll(0);

            $result = $producer->flush($this->flushTimeoutMs);

            if ($result !== RD_KAFKA_RESP_ERR_NO_ERROR) {
                Log::warning('Kafka flush incomplete', ['topic' => $topic, 'result' => $result]);
            }

            Log::debug('Kafka message published', ['topic' => $topic]);

            return $result === RD_KAFKA_RESP_ERR_NO_ERROR;
        } catch (\Throwable $e) {
            Log::error('Kafka publish error', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function subscribe(string $topic, callable $callback): void
    {
        try {
            $consumer = $this->getConsumer($topic);

            while (true) {
                $message = $consumer->consume($this->timeoutMs);

                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        try {
                            $payload = json_decode($message->payload, true, 512, JSON_THROW_ON_ERROR);
                            $callback($payload);
                            $consumer->commit($message);
                        } catch (\Throwable $e) {
                            Log::error('Kafka consumer callback error', ['error' => $e->getMessage()]);
                        }
                        break;

                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                        Log::debug('Kafka: partition EOF reached');
                        break;

                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        // No message within timeout – continue polling
                        break;

                    default:
                        Log::error('Kafka consumer error', [
                            'error' => $message->errstr(),
                            'code'  => $message->err,
                        ]);
                        break;
                }
            }
        } catch (\Throwable $e) {
            Log::error('Kafka subscribe error', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function disconnect(): void
    {
        try {
            if ($this->consumer !== null) {
                $this->consumer->unsubscribe();
                $this->consumer = null;
            }

            if ($this->producer !== null) {
                $this->producer->flush($this->flushTimeoutMs);
                $this->producer = null;
            }

            Log::debug('Kafka disconnected');
        } catch (\Throwable $e) {
            Log::warning('Kafka disconnect error', ['error' => $e->getMessage()]);
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
