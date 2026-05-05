<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class PaymentTermData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $name,
        public readonly int $days = 30,
        public readonly bool $is_default = false,
        public readonly bool $is_active = true,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
        public readonly ?string $description = null,
        public readonly ?int $discount_days = null,
        public readonly ?float $discount_rate = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            name: (string) $data['name'],
            days: (int) ($data['days'] ?? 30),
            is_default: (bool) ($data['is_default'] ?? false),
            is_active: (bool) ($data['is_active'] ?? true),
            rowVersion: (int) ($data['row_version'] ?? 1),
            id: isset($data['id']) ? (int) $data['id'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            discount_days: isset($data['discount_days']) ? (int) $data['discount_days'] : null,
            discount_rate: isset($data['discount_rate']) ? (float) $data['discount_rate'] : null,
        );
    }
}
