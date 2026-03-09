<?php
declare(strict_types=1);
namespace App\Services;
use App\Contracts\Services\NotificationServiceInterface;
use App\Domain\Notification\Models\NotificationLog;
use App\Domain\Notification\Models\WebhookEndpoint;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
class NotificationService implements NotificationServiceInterface {
    public function __construct(private readonly LoggerInterface $logger) {}
    public function send(array $data): bool {
        $log = NotificationLog::create([
            'tenant_id' => $data['tenant_id'],
            'channel' => $data['channel'] ?? 'email',
            'recipient' => $data['recipient'] ?? $data['customer_id'] ?? '',
            'event' => $data['event'],
            'template' => $data['template'] ?? 'default',
            'payload' => $data,
            'status' => NotificationLog::STATUS_PENDING,
            'saga_id' => $data['saga_id'] ?? null,
        ]);
        try {
            $sent = match ($data['channel'] ?? 'email') {
                'email' => $this->sendEmail($data),
                'webhook' => $this->dispatchWebhookForNotification($data),
                default => $this->logNotification($data),
            };
            $log->update(['status' => $sent ? NotificationLog::STATUS_SENT : NotificationLog::STATUS_FAILED, 'sent_at' => now()]);
            if (!empty($data['tenant_id'])) {
                $this->dispatchWebhook($data['tenant_id'], $data['event'], $data);
            }
            return $sent;
        } catch (\Throwable $e) {
            $log->update(['status' => NotificationLog::STATUS_FAILED, 'error_message' => $e->getMessage()]);
            $this->logger->error('Notification failed', ['event' => $data['event'], 'error' => $e->getMessage()]);
            return false;
        }
    }
    public function dispatchWebhook(string $tenantId, string $event, array $payload): void {
        $endpoints = WebhookEndpoint::where('tenant_id', $tenantId)->where('is_active', true)->whereJsonContains('events', $event)->get();
        foreach ($endpoints as $endpoint) { $this->sendWebhookToEndpoint($endpoint, $event, $payload); }
    }
    public function registerWebhook(array $data): WebhookEndpoint { return WebhookEndpoint::create($data); }
    private function sendEmail(array $data): bool {
        $this->logger->info('Email notification', ['recipient' => $data['recipient'] ?? 'unknown', 'event' => $data['event']]);
        return true;
    }
    private function dispatchWebhookForNotification(array $data): bool {
        if (!empty($data['webhook_url'])) {
            $client = new Client(['timeout' => (int) config('webhooks.timeout', 10)]);
            $client->post($data['webhook_url'], ['json' => $data]);
        }
        return true;
    }
    private function logNotification(array $data): bool {
        $this->logger->info('Notification logged (no channel handler)', ['event' => $data['event']]);
        return true;
    }
    private function sendWebhookToEndpoint(WebhookEndpoint $endpoint, string $event, array $payload): void {
        $maxRetries = (int) config('webhooks.max_retries', 3);
        $attempt = 0;
        $body = json_encode(['event' => $event, 'payload' => $payload, 'timestamp' => now()->toISOString(), 'webhook_id' => (string) Str::uuid()]);
        $signature = 'sha256=' . hash_hmac('sha256', $body, $endpoint->secret);
        $headers = array_merge($endpoint->headers ?? [], ['Content-Type' => 'application/json', 'X-Webhook-Signature' => $signature, 'X-Webhook-Event' => $event, 'X-Tenant-ID' => $endpoint->tenant_id]);
        while ($attempt < $maxRetries) {
            try {
                (new Client(['timeout' => (int) config('webhooks.timeout', 10)]))->post($endpoint->url, ['headers' => $headers, 'body' => $body]);
                $this->logger->info('Webhook delivered', ['endpoint' => $endpoint->name, 'event' => $event]);
                return;
            } catch (\Throwable $e) {
                $attempt++;
                $this->logger->warning('Webhook delivery failed', ['endpoint' => $endpoint->name, 'attempt' => $attempt, 'error' => $e->getMessage()]);
                if ($attempt < $maxRetries) { sleep(2 ** $attempt); }
            }
        }
        $this->logger->error('Webhook delivery failed after max retries', ['endpoint' => $endpoint->name, 'url' => $endpoint->url, 'event' => $event]);
    }
}
