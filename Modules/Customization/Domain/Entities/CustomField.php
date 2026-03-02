<?php

declare(strict_types=1);

namespace Modules\Customization\Domain\Entities;

class CustomField
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly string $entityType,
        public readonly string $fieldKey,
        public readonly string $fieldLabel,
        public readonly string $fieldType,
        public readonly bool $isRequired,
        public readonly ?string $defaultValue,
        public readonly int $sortOrder,
        public readonly ?array $options,
        public readonly ?string $validationRules,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
