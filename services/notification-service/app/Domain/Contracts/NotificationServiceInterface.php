<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\Entities\WebhookSubscription;

/**
 * Notification Service Interface
 */
interface NotificationServiceInterface
{
    public function send(string $event, array $payload, array $options = []): bool;

    public function dispatchWebhook(string $event, int|string $tenantId, array $payload): int;

    public function registerWebhook(int|string $tenantId, array $data): WebhookSubscription;

    public function handleEvent(string $event, array $payload): void;
}
