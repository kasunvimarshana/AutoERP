<?php

declare(strict_types=1);

namespace KvSaas\Contracts\Interfaces;

/**
 * MessageBrokerInterface
 *
 * Provides a unified, broker-agnostic API for publishing and consuming
 * messages.  Concrete adapters may wrap RabbitMQ, Kafka, SQS, etc.
 */
interface MessageBrokerInterface
{
    /**
     * Publish a message to an exchange / topic.
     *
     * @param  string               $exchange    Exchange or topic name
     * @param  string               $routingKey  Routing key / partition key
     * @param  array<string, mixed> $payload     Serialisable message body
     * @param  array<string, mixed> $headers     Optional message headers
     * @return void
     */
    public function publish(
        string $exchange,
        string $routingKey,
        array  $payload,
        array  $headers = []
    ): void;

    /**
     * Subscribe a handler to a queue / consumer group.
     *
     * The $handler callable receives:
     *   - array $message  Decoded message body
     *   - array $headers  Message headers
     *
     * @param  string   $queue    Queue or consumer group name
     * @param  callable $handler
     * @param  array<string, mixed> $options  Broker-specific options
     * @return void
     */
    public function subscribe(
        string   $queue,
        callable $handler,
        array    $options = []
    ): void;

    /**
     * Acknowledge that a message has been processed successfully.
     *
     * @param  mixed $messageId  Broker-specific message identifier
     * @return void
     */
    public function acknowledge(mixed $messageId): void;

    /**
     * Reject (NACK) a message, optionally re-queuing it.
     *
     * @param  mixed $messageId
     * @param  bool  $requeue
     * @return void
     */
    public function reject(mixed $messageId, bool $requeue = false): void;
}
