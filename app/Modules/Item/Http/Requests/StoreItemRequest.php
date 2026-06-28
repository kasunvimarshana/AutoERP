<?php

declare(strict_types=1);

namespace Modules\Item\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Http\Requests\Concerns\MapsItemData;

final class StoreItemRequest extends TenantScopedRequest
{
    use MapsItemData;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'item_type' => ['required', Rule::enum(ItemType::class)],
            'tracking_type' => ['nullable', Rule::enum(TrackingType::class)],
            'costing_method' => ['nullable', Rule::enum(CostingMethod::class)],
            'item_category_id' => ['nullable', 'integer', 'min:1'],
            'item_brand_id' => ['nullable', 'integer', 'min:1'],
            'sku' => ['nullable', 'string', 'max:120'],
            'barcode' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'base_uom_id' => ['nullable', 'integer', 'min:1'],
            'standard_price' => ['prohibited'],
            'default_tax_group_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('tax_groups', 'id')],
            'purchase_tax_group_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('tax_groups', 'id')],
            'sales_tax_group_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('tax_groups', 'id')],
            'is_stockable' => ['nullable', 'boolean'],
            'is_combo' => ['nullable', 'boolean'],
            'is_tax_exempt' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function toData(): CreateItemData
    {
        return $this->mapItemData($this->validated());
    }
}
