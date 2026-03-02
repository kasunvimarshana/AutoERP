<?php

declare(strict_types=1);

namespace Modules\Customization\Application\Commands;

class UpdateCustomFieldCommand
{
    public function __construct(
        public readonly int $id,
        public readonly int $tenantId,
        public readonly string $fieldLabel,
        public readonly bool $isRequired,
        public readonly ?string $defaultValue,
        public readonly int $sortOrder,
        public readonly ?array $options,
        public readonly ?string $validationRules,
    ) {}

    public function rules(): array
    {
        return [
            'id' => 'required|integer|min:1',
            'tenantId' => 'required|integer|min:1',
            'fieldLabel' => 'required|string|max:255',
            'isRequired' => 'required|boolean',
            'sortOrder' => 'required|integer|min:0',
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenantId,
            'fieldLabel' => $this->fieldLabel,
            'isRequired' => $this->isRequired,
            'sortOrder' => $this->sortOrder,
        ];
    }
}
