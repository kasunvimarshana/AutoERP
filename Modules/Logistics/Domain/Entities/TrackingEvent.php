<?php

namespace Modules\Logistics\Domain\Entities;

class TrackingEvent
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $tenant_id,
        public readonly string  $delivery_order_id,
        public readonly string  $event_type,
        public readonly ?string $location,
        public readonly string  $description,
        public readonly string  $occurred_at,
        public readonly string  $created_at,
        public readonly string  $updated_at,
    ) {}
}
