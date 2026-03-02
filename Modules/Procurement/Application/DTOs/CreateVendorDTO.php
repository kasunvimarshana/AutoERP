<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\DTOs;

use Modules\Core\Application\DTOs\DataTransferObject;

/**
 * Data Transfer Object for creating a Vendor.
 */
final class CreateVendorDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $address,
        public readonly ?string $vendorCode,
        public readonly bool $isActive,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            name: (string) $data['name'],
            email: isset($data['email']) ? (string) $data['email'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            address: isset($data['address']) ? (string) $data['address'] : null,
            vendorCode: isset($data['vendor_code']) ? (string) $data['vendor_code'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name'        => $this->name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'address'     => $this->address,
            'vendor_code' => $this->vendorCode,
            'is_active'   => $this->isActive,
        ];
    }
}
