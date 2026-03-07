<?php

declare(strict_types=1);

namespace App\Infrastructure\Webhook\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for webhook delivery and management.
 */
interface WebhookServiceInterface
{
    /**
     * Register a new webhook endpoint for a tenant.
     *
     * @param  int|string           $tenantId
     * @param  string               $url       Target URL (HTTPS recommended).
     * @param  string[]             $events    List of event types to subscribe to.
     * @param  array<string, mixed> $options   Extra options (secret, retries, headers).
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function register(int|string $tenantId, string $url, array $events, array $options = []): \Illuminate\Database\Eloquent\Model;

    /**
     * Dispatch a webhook payload to all registered endpoints for a given event.
     *
     * @param  string               $event    Event type (e.g. "product.created").
     * @param  array<string, mixed> $payload  Event data.
     * @param  int|string           $tenantId
     */
    public function dispatch(string $event, array $payload, int|string $tenantId): void;

    /**
     * Deliver a webhook to a single endpoint URL (used for retries).
     *
     * @return array{status_code: int, response_body: string, delivered_at: string}
     */
    public function deliver(string $url, string $secret, array $payload): array;

    /**
     * Return all webhooks for a tenant.
     */
    public function getForTenant(int|string $tenantId): Collection;

    /**
     * Delete a webhook registration.
     */
    public function delete(int|string $webhookId): bool;
}
