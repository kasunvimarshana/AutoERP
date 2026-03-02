<?php

declare(strict_types=1);

namespace Modules\Customization\Domain\Entities;

class CustomFieldValue
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly int $fieldId,
        public readonly ?string $value,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
