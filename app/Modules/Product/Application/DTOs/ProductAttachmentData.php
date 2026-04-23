<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class ProductAttachmentData
{
    /** @param array<string,mixed>|null $metadata */
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $product_id,
        public readonly string $file_name,
        public readonly string $file_path,
        public readonly string $file_type,
        public readonly int $file_size,
        public readonly ?int $variant_id = null,
        public readonly string $type = 'image',
        public readonly bool $is_primary = false,
        public readonly int $sort_order = 0,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?array $metadata = null,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            product_id: (int) $data['product_id'],
            file_name: (string) $data['file_name'],
            file_path: (string) $data['file_path'],
            file_type: (string) $data['file_type'],
            file_size: (int) $data['file_size'],
            variant_id: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            type: (string) ($data['type'] ?? 'image'),
            is_primary: (bool) ($data['is_primary'] ?? false),
            sort_order: (int) ($data['sort_order'] ?? 0),
            title: isset($data['title']) ? (string) $data['title'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
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
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'type' => $this->type,
            'is_primary' => $this->is_primary,
            'sort_order' => $this->sort_order,
            'title' => $this->title,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ];
    }
}
