<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\DTOs;

class SupplierContactData
{
    public function __construct(
        public readonly int $supplier_id,
        public readonly string $name,
        public readonly ?string $role = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly bool $is_primary = false,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            supplier_id: (int) $data['supplier_id'],
            name: (string) $data['name'],
            role: isset($data['role']) ? (string) $data['role'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            is_primary: (bool) ($data['is_primary'] ?? false),
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
