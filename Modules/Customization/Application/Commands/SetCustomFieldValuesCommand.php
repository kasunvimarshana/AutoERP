<?php

declare(strict_types=1);

namespace Modules\Customization\Application\Commands;

class SetCustomFieldValuesCommand
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly array $values,
    ) {}

    public function rules(): array
    {
        return [
            'tenantId' => 'required|integer|min:1',
            'entityType' => 'required|string|max:100',
            'entityId' => 'required|integer|min:1',
            'values' => 'required|array',
        ];
    }

    public function toArray(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'entityType' => $this->entityType,
            'entityId' => $this->entityId,
            'values' => $this->values,
        ];
    }
}
