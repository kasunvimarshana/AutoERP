<?php

namespace App\DTOs;

class WebhookPayloadDTO
{
    public function __construct(
        public readonly string $event,
        public readonly int    $tenantId,
        public readonly array  $data,
        public readonly string $timestamp,
        public readonly string $serviceSource = 'inventory-service',
    ) {}

    public static function fromArray(array $payload): self
    {
        return new self(
            event:         (string) $payload['event'],
            tenantId:      (int) $payload['tenant_id'],
            data:          (array) ($payload['data'] ?? []),
            timestamp:     (string) ($payload['timestamp'] ?? now()->toIso8601String()),
            serviceSource: (string) ($payload['service_source'] ?? 'inventory-service'),
        );
    }

    public function toArray(): array
    {
        return [
            'event'          => $this->event,
            'tenant_id'      => $this->tenantId,
            'data'           => $this->data,
            'timestamp'      => $this->timestamp,
            'service_source' => $this->serviceSource,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
