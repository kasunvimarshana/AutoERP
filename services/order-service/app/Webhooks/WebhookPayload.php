<?php

namespace App\Webhooks;

use App\DTOs\WebhookPayloadDTO;

class WebhookPayload
{
    private string $secret;

    public function __construct(?string $secret = null)
    {
        $this->secret = $secret ?? config('services.webhook_secret', '');
    }

    public function build(string $event, array $payload, string $tenantId): WebhookPayloadDTO
    {
        return new WebhookPayloadDTO(
            event:     $event,
            payload:   $payload,
            timestamp: now()->toIso8601String(),
            tenantId:  $tenantId,
            webhookId: \Ramsey\Uuid\Uuid::uuid4()->toString(),
        );
    }

    public function sign(array $body): string
    {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return 'sha256=' . hash_hmac('sha256', $json, $this->secret);
    }

    public function verify(string $signature, array $body): bool
    {
        $expected = $this->sign($body);

        return hash_equals($expected, $signature);
    }
}
