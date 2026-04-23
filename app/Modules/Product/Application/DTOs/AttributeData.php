<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class AttributeData
{
    /** @param array<string,mixed>|null $metadata */
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $name,
        public readonly string $type = 'select',
        public readonly bool $is_required = false,
        public readonly ?int $group_id = null,
        public readonly ?string $code = null,
        public readonly ?string $description = null,
        public readonly int $sort_order = 0,
        public readonly bool $is_active = true,
        public readonly bool $is_filterable = false,
        public readonly ?array $metadata = null,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            name: (string) $data['name'],
            type: (string) ($data['type'] ?? 'select'),
            is_required: (bool) ($data['is_required'] ?? false),
            group_id: isset($data['group_id']) ? (int) $data['group_id'] : null,
            code: isset($data['code']) ? (string) $data['code'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            sort_order: (int) ($data['sort_order'] ?? 0),
            is_active: (bool) ($data['is_active'] ?? true),
            is_filterable: (bool) ($data['is_filterable'] ?? false),
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'group_id' => $this->group_id,
            'name' => $this->name,
            'type' => $this->type,
            'is_required' => $this->is_required,
            'code' => $this->code,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'is_filterable' => $this->is_filterable,
            'metadata' => $this->metadata,
        ];
    }
}
