<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Entities;

/**
 * Domain entity representing a Permission.
 */
final class Permission
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description = null,
        public readonly ?string $module = null,
        public readonly string $guardName = 'api',
        public readonly ?array $metadata = null,
        public readonly ?\DateTimeInterface $createdAt = null,
        public readonly ?\DateTimeInterface $updatedAt = null,
    ) {}
}
