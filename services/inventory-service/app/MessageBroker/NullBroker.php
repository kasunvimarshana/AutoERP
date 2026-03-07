<?php

namespace App\MessageBroker;

use App\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;

/**
 * No-op broker for local development and testing.
 * All messages are silently discarded (logged at debug level).
 */
class NullBroker implements MessageBrokerInterface
{
    public function publish(string $topic, array $payload): bool
    {
        Log::debug('NullBroker: message dropped', ['topic' => $topic, 'payload' => $payload]);

        return true;
    }

    public function subscribe(string $topic, callable $callback): void
    {
        Log::debug('NullBroker: subscribe called (no-op)', ['topic' => $topic]);
    }

    public function disconnect(): void
    {
        // Nothing to disconnect
    }
}
