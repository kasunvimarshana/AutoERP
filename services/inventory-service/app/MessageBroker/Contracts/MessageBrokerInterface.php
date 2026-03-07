<?php

namespace App\MessageBroker\Contracts;

interface MessageBrokerInterface
{
    /**
     * Publish a message to the given topic/routing key.
     */
    public function publish(string $topic, array $payload): bool;

    /**
     * Subscribe to a topic and process incoming messages via a callback.
     *
     * The callback receives a decoded (array) payload.
     * This method blocks until consumption ends or an error occurs.
     */
    public function subscribe(string $topic, callable $callback): void;

    /**
     * Cleanly disconnect from the broker.
     */
    public function disconnect(): void;
}
