<?php

namespace App\DTOs;

readonly class WebhookPayloadDTO
{
    public function __construct(
        public string $event,
        public array  $payload,
        public string $timestamp,
        public string $tenantId,
        public string $webhookId = '',
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            event:     $data['event'],
            payload:   $data['payload'] ?? [],
            timestamp: $data['timestamp'] ?? now()->toIso8601String(),
            tenantId:  $data['tenant_id'],
            webhookId: $data['webhook_id'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'event'      => $this->event,
            'payload'    => $this->payload,
            'timestamp'  => $this->timestamp,
            'tenant_id'  => $this->tenantId,
            'webhook_id' => $this->webhookId,
        ];
    }
}
