<?php

namespace App\DTOs;

class WarehouseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $address = null,
        public readonly ?string $city = null,
        public readonly ?string $country = null,
        public readonly bool $isActive = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            name: $data['name'],
            code: strtoupper($data['code']),
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            country: $data['country'] ?? null,
            isActive: $data['is_active'] ?? true,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'is_active' => $this->isActive,
        ];
    }
}
