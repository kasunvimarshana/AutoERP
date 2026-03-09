<?php
declare(strict_types=1);
namespace App\Contracts\Services;
interface NotificationServiceInterface {
    public function send(array $data): bool;
    public function dispatchWebhook(string $tenantId, string $event, array $payload): void;
    public function registerWebhook(array $data): \App\Domain\Notification\Models\WebhookEndpoint;
}
