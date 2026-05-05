<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\DTOs;

class SupplierAddressData
{
    public function __construct(
        public readonly int $supplierId,
        public readonly string $type,
        public readonly string $addressLine1,
        public readonly string $city,
        public readonly string $postalCode,
        public readonly int $countryId,
        public readonly ?string $label = null,
        public readonly ?string $addressLine2 = null,
        public readonly ?string $state = null,
        public readonly bool $isDefault = false,
        public readonly ?string $geoLat = null,
        public readonly ?string $geoLng = null,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            supplierId: (int) $data['supplier_id'],
            type: (string) ($data['type'] ?? 'billing'),
            addressLine1: (string) $data['address_line1'],
            city: (string) $data['city'],
            postalCode: (string) $data['postal_code'],
            countryId: (int) $data['country_id'],
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
