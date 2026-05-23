<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\DTOs;

final readonly class OrganizationUnitTypeData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public string $name,
        public int $level = 0,
        public bool $isActive = true,
        public ?array $metadata = null,
    ) {}

    public static function fromArray(int|string $tenantId, array $data): self
    {
        return new self(
            tenantId: (int) $tenantId,
            name: (string) $data['name'],
            level: (int) ($data['level'] ?? 0),
            isActive: (bool) ($data['is_active'] ?? true),
            metadata: $data['metadata'] ?? null,
        );
    }
}
