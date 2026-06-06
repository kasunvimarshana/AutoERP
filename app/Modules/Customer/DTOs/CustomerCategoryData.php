<?php

declare(strict_types=1);

namespace Modules\Customer\DTOs;

final readonly class CustomerCategoryData
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
