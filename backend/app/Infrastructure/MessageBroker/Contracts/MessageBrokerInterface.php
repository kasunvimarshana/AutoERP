<?php

declare(strict_types=1);

namespace App\Infrastructure\MessageBroker\Contracts;

/**
 * Pluggable message broker interface.
 *
 * Concrete implementations: KafkaMessageBroker, RabbitMQMessageBroker.
 * A NullMessageBroker (no-op) can be bound in testing.
 */
interface MessageBrokerInterface
{
    /**
     * Publish a message to the given topic / exchange / queue.
     *
     * @param  string               $topic    Topic name (e.g. "inventory.product.created").
     * @param  array<string, mixed> $payload  Message body.
     * @param  array<string, mixed> $options  Driver-specific options (partition, headers, etc.).
     */
    public function publish(string $topic, array $payload, array $options = []): void;

    /**
     * Subscribe to one or more topics and invoke a callback for each message.
     *
     * @param  string[]                          $topics    Topics to subscribe to.
     * @param  callable(array $message): void    $callback  Handler invoked per message.
     * @param  array<string, mixed>              $options   Driver-specific options.
     */
    public function subscribe(array $topics, callable $callback, array $options = []): void;

    /**
     * Acknowledge a successfully processed message (where applicable).
     *
     * @param  mixed $messageId  Driver-specific message identifier.
     */
    public function acknowledge(mixed $messageId): void;

    /**
     * Reject / nack a message, optionally requeueing it.
     *
     * @param  mixed $messageId  Driver-specific message identifier.
     * @param  bool  $requeue    Whether to put the message back in the queue.
     */
    public function reject(mixed $messageId, bool $requeue = false): void;

    /**
     * Check whether the broker connection is healthy.
     */
    public function isHealthy(): bool;

    /**
     * Return the name of the active driver (e.g. "kafka", "rabbitmq").
     */
    public function getDriver(): string;
}
