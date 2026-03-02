<?php

declare(strict_types=1);

namespace Modules\Organisation\Domain\Entities;

final class Organisation
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly ?int $parentId,
        public readonly string $type,
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $description,
        public readonly string $status,
        public readonly ?array $meta,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
