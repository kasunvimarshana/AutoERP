<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\DTOs;

final readonly class WarehouseData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public string $name,
        public ?string $code = null,
        public ?string $imagePath = null,
        public string $type = 'standard',
        public bool $isActive = true,
        public bool $isDefault = false,
        public ?array $metadata = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int|string $tenantId, array $data): self
    {
        return new self(
            tenantId: (int) $tenantId,
            organizationUnitId: isset($data['organization_unit_id']) ? (int) $data['organization_unit_id'] : null,
            name: (string) $data['name'],
            code: $data['code'] ?? null,
            imagePath: $data['image_path'] ?? null,
            type: (string) ($data['type'] ?? 'standard'),
            isActive: (bool) ($data['is_active'] ?? true),
            isDefault: (bool) ($data['is_default'] ?? false),
            metadata: $data['metadata'] ?? null,
        );
    }
}
