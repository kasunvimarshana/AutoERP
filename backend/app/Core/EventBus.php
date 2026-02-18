<?php

declare(strict_types=1);

namespace App\Core;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * EventBus - Central event management for inter-module communication
 * 
 * Provides a clean abstraction for event-driven architecture
 * enabling loose coupling between modules.
 */
class EventBus
{
    /**
     * Dispatch an event
     */
    public function dispatch(string $eventName, array $payload = []): void
    {
        Log::debug("EventBus: Dispatching event '{$eventName}'", ['payload' => $payload]);
        
        Event::dispatch($eventName, $payload);
    }

    /**
     * Listen to an event
     */
    public function listen(string $eventName, callable $callback): void
    {
        Event::listen($eventName, $callback);
    }

    /**
     * Subscribe to multiple events
     */
    public function subscribe(string $subscriberClass): void
    {
        Event::subscribe($subscriberClass);
    }

    /**
     * Check if event has listeners
     */
    public function hasListeners(string $eventName): bool
    {
        return Event::hasListeners($eventName);
    }

    /**
     * Forget all listeners for an event
     */
    public function forget(string $eventName): void
    {
        Event::forget($eventName);
    }

    /**
     * Dispatch event until first non-null response
     */
    public function until(string $eventName, array $payload = [])
    {
        return Event::until($eventName, $payload);
    }
}
