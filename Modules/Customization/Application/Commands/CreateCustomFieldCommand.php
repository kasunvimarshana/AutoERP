<?php

declare(strict_types=1);

namespace Modules\Customization\Application\Commands;

class CreateCustomFieldCommand
{
    public function __construct(
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
    ) {}

    public function rules(): array
    {
        return [
            'tenantId' => 'required|integer|min:1',
            'entityType' => 'required|string|max:100',
            'fieldKey' => 'required|string|max:100',
            'fieldLabel' => 'required|string|max:255',
            'fieldType' => 'required|string',
            'isRequired' => 'required|boolean',
            'sortOrder' => 'required|integer|min:0',
        ];
    }

    public function toArray(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'entityType' => $this->entityType,
            'fieldKey' => $this->fieldKey,
            'fieldLabel' => $this->fieldLabel,
            'fieldType' => $this->fieldType,
            'isRequired' => $this->isRequired,
            'sortOrder' => $this->sortOrder,
        ];
    }
}
