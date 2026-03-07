<?php

namespace App\DTOs;

class WebhookPayloadDTO
{
    public function __construct(
        public readonly string $event,
        public readonly string $tenantId,
        public readonly array  $data,
        public readonly string $timestamp,
        public readonly string $id,
    ) {}

    public static function make(string $event, string $tenantId, array $data): self
    {
        return new self(
            event:     $event,
            tenantId:  $tenantId,
            data:      $data,
            timestamp: now()->toIso8601String(),
            id:        (string) \Illuminate\Support\Str::uuid(),
        );
    }

    public function toArray(): array
    {
        return [
            'id'        => $this->id,
            'event'     => $this->event,
            'tenant_id' => $this->tenantId,
            'data'      => $this->data,
            'timestamp' => $this->timestamp,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
