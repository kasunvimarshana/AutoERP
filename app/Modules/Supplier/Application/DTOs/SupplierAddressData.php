<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\DTOs;

class SupplierAddressData
{
    public function __construct(
        public readonly int $supplier_id,
        public readonly string $type,
        public readonly string $address_line1,
        public readonly string $city,
        public readonly string $postal_code,
        public readonly int $country_id,
        public readonly ?string $label = null,
        public readonly ?string $address_line2 = null,
        public readonly ?string $state = null,
        public readonly bool $is_default = false,
        public readonly ?string $geo_lat = null,
        public readonly ?string $geo_lng = null,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            supplier_id: (int) $data['supplier_id'],
            type: (string) ($data['type'] ?? 'billing'),
            address_line1: (string) $data['address_line1'],
            city: (string) $data['city'],
            postal_code: (string) $data['postal_code'],
            country_id: (int) $data['country_id'],
            label: isset($data['label']) ? (string) $data['label'] : null,
            address_line2: isset($data['address_line2']) ? (string) $data['address_line2'] : null,
            state: isset($data['state']) ? (string) $data['state'] : null,
            is_default: (bool) ($data['is_default'] ?? false),
            geo_lat: isset($data['geo_lat']) ? (string) $data['geo_lat'] : null,
            geo_lng: isset($data['geo_lng']) ? (string) $data['geo_lng'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
