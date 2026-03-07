<?php

namespace App\MessageBroker;

use App\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Support\Facades\Log;

/**
 * No-op broker used in testing or when no real broker is configured.
 * Publish calls are logged; subscribe is a no-op.
 */
class NullBroker implements MessageBrokerInterface
{
    public function publish(string $topic, array $payload): bool
    {
        Log::debug('NullBroker::publish (no-op)', ['topic' => $topic, 'payload' => $payload]);

        return true;
    }

    public function subscribe(string $topic, callable $callback): void
    {
        Log::debug('NullBroker::subscribe (no-op)', ['topic' => $topic]);
    }

    public function disconnect(): void
    {
        Log::debug('NullBroker::disconnect (no-op)');
    }
}
