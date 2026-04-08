<?php

declare(strict_types=1);

namespace Modules\Configuration\Domain\Entities;

use DateTimeInterface;

class OrgUnit
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $type,
        public readonly string $code,
        public readonly ?string $parentId,
        public readonly string $path,
        public readonly int $level,
        public readonly bool $isActive,
        public readonly array $metadata,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function isDescendantOf(OrgUnit $other): bool
    {
        return str_starts_with($this->path, $other->path) && $this->path !== $other->path;
    }

    public function isAncestorOf(OrgUnit $other): bool
    {
        return str_starts_with($other->path, $this->path) && $this->path !== $other->path;
    }
}
