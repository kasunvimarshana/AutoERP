<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Entities;

/**
 * Domain entity representing a Role.
 */
final class Role
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?int $tenantId = null,
        public readonly ?string $description = null,
        public readonly bool $isSystem = false,
        public readonly string $guardName = 'api',
        public readonly ?array $metadata = null,
        public readonly ?\DateTimeInterface $createdAt = null,
        public readonly ?\DateTimeInterface $updatedAt = null,
    ) {}

    public function isGlobal(): bool
    {
        return $this->tenantId === null;
    }
}
