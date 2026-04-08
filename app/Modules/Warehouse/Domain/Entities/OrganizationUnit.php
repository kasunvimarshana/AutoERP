<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

class OrganizationUnit
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly ?string $parentId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly bool $isActive,
    ) {}
}
