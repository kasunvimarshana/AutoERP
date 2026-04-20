<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class ProductIdentifierData
{
    /**
     * @param  array<string, mixed>|null  $gs1_application_identifiers
     * @param  array<string, mixed>|null  $format_config
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $product_id,
        public readonly string $technology,
        public readonly string $value,
        public readonly ?int $variant_id = null,
        public readonly ?int $batch_id = null,
        public readonly ?int $serial_id = null,
        public readonly ?string $format = null,
        public readonly ?string $gs1_company_prefix = null,
        public readonly ?array $gs1_application_identifiers = null,
        public readonly bool $is_primary = false,
        public readonly bool $is_active = true,
        public readonly ?array $format_config = null,
        public readonly ?array $metadata = null,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            product_id: (int) $data['product_id'],
            technology: (string) $data['technology'],
            value: (string) $data['value'],
            variant_id: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            batch_id: isset($data['batch_id']) ? (int) $data['batch_id'] : null,
            serial_id: isset($data['serial_id']) ? (int) $data['serial_id'] : null,
            format: isset($data['format']) ? (string) $data['format'] : null,
            gs1_company_prefix: isset($data['gs1_company_prefix']) ? (string) $data['gs1_company_prefix'] : null,
            gs1_application_identifiers: isset($data['gs1_application_identifiers']) && is_array($data['gs1_application_identifiers']) ? $data['gs1_application_identifiers'] : null,
            is_primary: (bool) ($data['is_primary'] ?? false),
            is_active: (bool) ($data['is_active'] ?? true),
            format_config: isset($data['format_config']) && is_array($data['format_config']) ? $data['format_config'] : null,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
