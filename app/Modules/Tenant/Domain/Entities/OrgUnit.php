<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Entities;

use DateTimeImmutable;

final class OrgUnit
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $tenantId,
        public readonly ?int $parentId,
        public readonly string $name,
        public readonly string $code,
        public readonly string $type,
        public readonly ?string $description,
        public readonly bool $isActive,
        public readonly int $sortOrder,
        public readonly ?array $metadata,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
        public readonly ?DateTimeImmutable $deletedAt = null,
    ) {}
}
