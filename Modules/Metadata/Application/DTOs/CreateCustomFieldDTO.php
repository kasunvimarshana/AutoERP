<?php

declare(strict_types=1);

namespace Modules\Metadata\Application\DTOs;

/**
 * Data Transfer Object for creating a CustomFieldDefinition.
 */
final class CreateCustomFieldDTO
{
    public function __construct(
        public readonly string $entityType,
        public readonly string $fieldName,
        public readonly string $fieldLabel,
        public readonly string $fieldType,
        public readonly ?array $options,
        public readonly bool $isRequired,
        public readonly bool $isActive,
        public readonly int $sortOrder,
        public readonly ?array $validationRules,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            entityType:      $data['entity_type'],
            fieldName:       $data['field_name'],
            fieldLabel:      $data['field_label'],
            fieldType:       $data['field_type'],
            options:         $data['options'] ?? null,
            isRequired:      (bool) ($data['is_required'] ?? false),
            isActive:        (bool) ($data['is_active'] ?? true),
            sortOrder:       (int) ($data['sort_order'] ?? 0),
            validationRules: $data['validation_rules'] ?? null,
        );
    }
}
