<?php

namespace App\Modules\Webhook\DTOs;

class WebhookPayloadDTO
{
    public function __construct(
        public readonly string $event,
        public readonly string $service,
        public readonly array $data,
        public readonly string $timestamp,
        public readonly string $version = '1.0',
    ) {}

    public static function create(string $event, string $service, array $data): self
    {
        return new self(
            event: $event,
            service: $service,
            data: $data,
            timestamp: now()->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'event' => $this->event,
            'service' => $this->service,
            'data' => $this->data,
            'timestamp' => $this->timestamp,
            'version' => $this->version,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
