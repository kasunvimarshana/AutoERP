<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'category_id' => $this->getCategoryId(),
            'brand_id' => $this->getBrandId(),
            'org_unit_id' => $this->getOrgUnitId(),
            'type' => $this->getType(),
            'name' => $this->getName(),
            'image_path' => $this->getImagePath(),
            'slug' => $this->getSlug(),
            'sku' => $this->getSku(),
            'description' => $this->getDescription(),
            'base_uom_id' => $this->getBaseUomId(),
            'purchase_uom_id' => $this->getPurchaseUomId(),
            'sales_uom_id' => $this->getSalesUomId(),
            'tax_group_id' => $this->getTaxGroupId(),
            'uom_conversion_factor' => $this->getUomConversionFactor(),
            'is_batch_tracked' => $this->isBatchTracked(),
            'is_lot_tracked' => $this->isLotTracked(),
            'is_serial_tracked' => $this->isSerialTracked(),
            'valuation_method' => $this->getValuationMethod(),
            'standard_cost' => $this->getStandardCost(),
            'income_account_id' => $this->getIncomeAccountId(),
            'cogs_account_id' => $this->getCogsAccountId(),
            'inventory_account_id' => $this->getInventoryAccountId(),
            'expense_account_id' => $this->getExpenseAccountId(),
            'is_active' => $this->isActive(),
            'status' => $this->getStatus(),
            'metadata' => $this->getMetadata(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
