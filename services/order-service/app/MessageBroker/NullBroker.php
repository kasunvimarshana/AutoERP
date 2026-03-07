<?php

namespace App\MessageBroker;

use App\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;

class NullBroker implements MessageBrokerInterface
{
    public function publish(string $exchange, string $routingKey, array $message): void
    {
        Log::info('NullBroker::publish (no-op)', [
            'exchange'    => $exchange,
            'routing_key' => $routingKey,
            'message'     => $message,
        ]);
    }

    public function subscribe(string $queue, callable $handler): void
    {
        Log::info('NullBroker::subscribe (no-op)', ['queue' => $queue]);
    }

    public function disconnect(): void
    {
        Log::info('NullBroker::disconnect (no-op)');
    }
}
