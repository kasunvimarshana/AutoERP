<?php

namespace App\Modules\Manufacturing\DTOs;

/**
 * Bill of Material Data Transfer Object
 *
 * Encapsulates data for BOM creation and updates.
 */
class CreateBillOfMaterialDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly string $bomNumber,
        public readonly ?int $version = 1,
        public readonly ?bool $isActive = true,
        public readonly ?string $effectiveDate = null,
        public readonly ?string $notes = null,
        public readonly ?array $items = null,
    ) {}

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['product_id'],
            bomNumber: $data['bom_number'],
            version: $data['version'] ?? 1,
            isActive: $data['is_active'] ?? true,
            effectiveDate: $data['effective_date'] ?? null,
            notes: $data['notes'] ?? null,
            items: $data['items'] ?? null,
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'bom_number' => $this->bomNumber,
            'version' => $this->version,
            'is_active' => $this->isActive,
            'effective_date' => $this->effectiveDate,
            'notes' => $this->notes,
        ];
    }
}
