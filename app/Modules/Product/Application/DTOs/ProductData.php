<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class ProductData
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $type,
        public readonly string $name,
        public readonly string $slug,
        public readonly int $base_uom_id,
        public readonly ?int $category_id = null,
        public readonly ?int $brand_id = null,
        public readonly ?int $org_unit_id = null,
        public readonly ?string $sku = null,
        public readonly ?string $description = null,
        public readonly ?int $purchase_uom_id = null,
        public readonly ?int $sales_uom_id = null,
        public readonly string $uom_conversion_factor = '1',
        public readonly bool $is_batch_tracked = false,
        public readonly bool $is_lot_tracked = false,
        public readonly bool $is_serial_tracked = false,
        public readonly string $valuation_method = 'fifo',
        public readonly ?string $standard_cost = null,
        public readonly ?int $income_account_id = null,
        public readonly ?int $cogs_account_id = null,
        public readonly ?int $inventory_account_id = null,
        public readonly ?int $expense_account_id = null,
        public readonly bool $is_active = true,
        public readonly ?array $metadata = null,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            type: (string) ($data['type'] ?? 'physical'),
            name: (string) $data['name'],
            slug: (string) $data['slug'],
            base_uom_id: (int) $data['base_uom_id'],
            category_id: isset($data['category_id']) ? (int) $data['category_id'] : null,
            brand_id: isset($data['brand_id']) ? (int) $data['brand_id'] : null,
            org_unit_id: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            sku: isset($data['sku']) ? (string) $data['sku'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            purchase_uom_id: isset($data['purchase_uom_id']) ? (int) $data['purchase_uom_id'] : null,
            sales_uom_id: isset($data['sales_uom_id']) ? (int) $data['sales_uom_id'] : null,
            uom_conversion_factor: (string) ($data['uom_conversion_factor'] ?? '1'),
            is_batch_tracked: (bool) ($data['is_batch_tracked'] ?? false),
            is_lot_tracked: (bool) ($data['is_lot_tracked'] ?? false),
            is_serial_tracked: (bool) ($data['is_serial_tracked'] ?? false),
            valuation_method: (string) ($data['valuation_method'] ?? 'fifo'),
            standard_cost: isset($data['standard_cost']) ? (string) $data['standard_cost'] : null,
            income_account_id: isset($data['income_account_id']) ? (int) $data['income_account_id'] : null,
            cogs_account_id: isset($data['cogs_account_id']) ? (int) $data['cogs_account_id'] : null,
            inventory_account_id: isset($data['inventory_account_id']) ? (int) $data['inventory_account_id'] : null,
            expense_account_id: isset($data['expense_account_id']) ? (int) $data['expense_account_id'] : null,
            is_active: (bool) ($data['is_active'] ?? true),
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'org_unit_id' => $this->org_unit_id,
            'type' => $this->type,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'base_uom_id' => $this->base_uom_id,
            'purchase_uom_id' => $this->purchase_uom_id,
            'sales_uom_id' => $this->sales_uom_id,
            'uom_conversion_factor' => $this->uom_conversion_factor,
            'is_batch_tracked' => $this->is_batch_tracked,
            'is_lot_tracked' => $this->is_lot_tracked,
            'is_serial_tracked' => $this->is_serial_tracked,
            'valuation_method' => $this->valuation_method,
            'standard_cost' => $this->standard_cost,
            'income_account_id' => $this->income_account_id,
            'cogs_account_id' => $this->cogs_account_id,
            'inventory_account_id' => $this->inventory_account_id,
            'expense_account_id' => $this->expense_account_id,
            'is_active' => $this->is_active,
            'metadata' => $this->metadata,
        ];
    }
}
