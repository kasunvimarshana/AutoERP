<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

/**
 * Data Transfer Object for creating a Product.
 */
final class CreateProductDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $sku,
        public readonly string $type,
        public readonly ?string $description,
        public readonly int $uomId,
        public readonly ?int $buyingUomId,
        public readonly ?int $sellingUomId,
        public readonly bool $isActive,
        public readonly bool $hasSerialTracking,
        public readonly bool $hasBatchTracking,
        public readonly bool $hasExpiryTracking,
        public readonly ?string $barcode,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name:               $data['name'],
            sku:                $data['sku'],
            type:               $data['type'],
            description:        $data['description'] ?? null,
            uomId:              (int) $data['uom_id'],
            buyingUomId:        isset($data['buying_uom_id']) ? (int) $data['buying_uom_id'] : null,
            sellingUomId:       isset($data['selling_uom_id']) ? (int) $data['selling_uom_id'] : null,
            isActive:           (bool) ($data['is_active'] ?? true),
            hasSerialTracking:  (bool) ($data['has_serial_tracking'] ?? false),
            hasBatchTracking:   (bool) ($data['has_batch_tracking'] ?? false),
            hasExpiryTracking:  (bool) ($data['has_expiry_tracking'] ?? false),
            barcode:            $data['barcode'] ?? null,
        );
    }
}
