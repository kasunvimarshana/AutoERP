<?php

namespace App\MessageBroker\Contracts;

interface MessageBrokerInterface
{
    /**
     * Publish a message to a topic.
     *
     * @param string $topic   The topic/channel/queue name.
     * @param array  $payload The message payload.
     * @param array  $options Driver-specific options (priority, delay, etc.)
     */
    public function publish(string $topic, array $payload, array $options = []): void;

    /**
     * Subscribe a handler to a topic.
     *
     * @param string   $topic   The topic/channel to listen on.
     * @param callable $handler Callback receiving the decoded payload array.
     */
    public function subscribe(string $topic, callable $handler): void;

    /**
     * Broadcast a message to all subscribers of a topic.
     *
     * @param string $topic   The topic to broadcast to.
     * @param array  $payload The message payload.
     */
    public function broadcast(string $topic, array $payload): void;

    /**
     * Acknowledge successful processing of a message.
     */
    public function ack(string $messageId): void;

    /**
     * Negatively acknowledge (reject) a message.
     */
    public function nack(string $messageId, bool $requeue = true): void;

    /**
     * Return the name of the active driver.
     */
    public function getDriver(): string;
}
