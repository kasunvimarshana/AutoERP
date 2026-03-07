<?php

namespace App\MessageBroker;

use App\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Producer;
use RdKafka\TopicConf;

class KafkaBroker implements MessageBrokerInterface
{
    private ?Producer $producer = null;

    public function __construct(
        private readonly string $brokers,
        private readonly string $groupId = 'order-service',
        private readonly int    $flushTimeoutMs = 10000,
    ) {
    }

    private function getProducer(): Producer
    {
        if ($this->producer !== null) {
            return $this->producer;
        }

        $conf = new Conf();
        $conf->set('metadata.broker.list', $this->brokers);
        $conf->set('socket.timeout.ms', '10000');
        $conf->setDrMsgCb(static function (Producer $kafka, \RdKafka\Message $message): void {
            if ($message->err !== RD_KAFKA_RESP_ERR_NO_ERROR) {
                Log::error('KafkaBroker: delivery error', [
                    'error' => $message->errstr(),
                    'topic' => $message->topic_name,
                ]);
            }
        });

        $this->producer = new Producer($conf);

        return $this->producer;
    }

    /**
     * Publish a message to a Kafka topic.
     * The `$exchange` parameter is used as the topic name; `$routingKey` is the message key.
     */
    public function publish(string $exchange, string $routingKey, array $message): void
    {
        $producer = $this->getProducer();

        $topicConf = new TopicConf();
        $topicConf->set('message.timeout.ms', '10000');

        $topic = $producer->newTopic($exchange, $topicConf);
        $body  = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $body, $routingKey);
        $producer->poll(0);

        $result = $producer->flush($this->flushTimeoutMs);

        if ($result !== RD_KAFKA_RESP_ERR_NO_ERROR) {
            throw new \RuntimeException('KafkaBroker: failed to flush producer queue. Error code: ' . $result);
        }

        Log::info('KafkaBroker::publish', ['topic' => $exchange, 'key' => $routingKey]);
    }

    /**
     * Subscribe to a Kafka topic (queue param used as topic name).
     * Blocks indefinitely consuming messages.
     */
    public function subscribe(string $queue, callable $handler): void
    {
        $conf = new Conf();
        $conf->set('metadata.broker.list', $this->brokers);
        $conf->set('group.id', $this->groupId);
        $conf->set('auto.offset.reset', 'earliest');
        $conf->set('enable.auto.commit', 'true');

        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe([$queue]);

        Log::info('KafkaBroker::subscribe', ['topic' => $queue, 'group' => $this->groupId]);

        while (true) {
            $message = $consumer->consume(120 * 1000);

            if ($message === null) {
                continue;
            }

            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $data = json_decode($message->payload, true) ?? [];
                    $handler($data);
                    break;

                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    Log::debug('KafkaBroker: end of partition');
                    break;

                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    Log::debug('KafkaBroker: consumer timed out waiting');
                    break;

                default:
                    Log::error('KafkaBroker: consumer error', [
                        'error' => $message->errstr(),
                        'code'  => $message->err,
                    ]);
            }
        }
    }

    public function disconnect(): void
    {
        if ($this->producer !== null) {
            $this->producer->flush($this->flushTimeoutMs);
            $this->producer = null;
        }

        Log::info('KafkaBroker::disconnect');
    }
}
