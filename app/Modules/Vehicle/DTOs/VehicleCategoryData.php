<?php

declare(strict_types=1);

namespace Modules\Vehicle\DTOs;

final readonly class VehicleCategoryData
{
    public function __construct(
        public int $tenantId,
        public string $code,
        public string $name,
        public ?int $organizationUnitId = null,
        public ?int $parentId = null,
        public ?string $description = null,
        public bool $isActive = true,
        public int $sortOrder = 0,
    ) {}
}
