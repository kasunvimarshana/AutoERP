<?php

namespace App\Services\MessageBroker;

interface MessageBrokerInterface
{
    public function publish(string $exchange, string $routingKey, array $message): void;
    public function consume(string $queue, callable $callback): void;
    public function declareExchange(string $exchange, string $type = 'topic'): void;
    public function declareQueue(string $queue, string $exchange, string $routingKey): void;
}
