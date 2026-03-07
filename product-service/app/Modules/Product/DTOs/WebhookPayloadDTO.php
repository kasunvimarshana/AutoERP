<?php

namespace App\Modules\Product\DTOs;

class WebhookPayloadDTO
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType, // e.g. "product.created"
        public readonly array $data,
        public readonly string $timestamp
    ) {
    }

    public static function create(string $eventType, array $data): self
    {
        return new self(
            eventId: uniqid('evt_', true),
            eventType: $eventType,
            data: $data,
            timestamp: now()->toIso8601String()
        );
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_type' => $this->eventType,
            'data' => $this->data,
            'timestamp' => $this->timestamp,
            'version' => '1.0'
        ];
    }
}
