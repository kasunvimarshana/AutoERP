<?php

namespace Modules\Logistics\Domain\Entities;

class DeliveryOrder
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $tenant_id,
        public readonly ?string $carrier_id,
        public readonly ?string $source_location_id,
        public readonly string  $reference_no,
        public readonly string  $origin_address,
        public readonly string  $destination_address,
        public readonly string  $scheduled_date,
        public readonly ?string $delivered_date,
        public readonly string  $status,
        public readonly string  $weight,
        public readonly string  $shipping_cost,
        public readonly ?string $notes,
        public readonly string  $created_at,
        public readonly string  $updated_at,
    ) {}
}
