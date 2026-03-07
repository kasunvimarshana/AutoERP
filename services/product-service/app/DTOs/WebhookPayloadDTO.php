<?php

namespace App\DTOs;

class WebhookPayloadDTO
{
    public function __construct(
        public readonly string $event,
        public readonly string $tenantId,
        public readonly array $data,
        public readonly string $timestamp,
        public readonly string $webhookId,
        public readonly int $version = 1,
    ) {}

    public static function make(string $event, string $tenantId, array $data): self
    {
        return new self(
            event:     $event,
            tenantId:  $tenantId,
            data:      $data,
            timestamp: now()->toIso8601String(),
            webhookId: (string) \Illuminate\Support\Str::uuid(),
        );
    }

    public function toArray(): array
    {
        return [
            'webhook_id' => $this->webhookId,
            'event'      => $this->event,
            'tenant_id'  => $this->tenantId,
            'version'    => $this->version,
            'timestamp'  => $this->timestamp,
            'data'       => $this->data,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    public function sign(string $secret): string
    {
        return hash_hmac('sha256', $this->toJson(), $secret);
    }
}
