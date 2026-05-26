<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => array_merge($required, ['integer', 'min:1', 'exists:tenants,id']),
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'category_id' => ['nullable', 'integer', 'min:1', 'exists:item_categories,id'],
            'brand_id' => ['nullable', 'integer', 'min:1', 'exists:item_brands,id'],
            'type' => ['nullable', 'string', 'max:255'],
            'name' => array_merge($required, ['string', 'max:255']),
            'slug' => ['nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'base_uom_id' => array_merge($required, ['integer', 'min:1', 'exists:unit_of_measures,id']),
            'purchase_uom_id' => ['nullable', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'sales_uom_id' => ['nullable', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
            'is_batch_tracked' => ['nullable', 'boolean'],
            'is_lot_tracked' => ['nullable', 'boolean'],
            'is_serial_tracked' => ['nullable', 'boolean'],
            'is_stockable' => ['nullable', 'boolean'],
            'standard_cost' => ['nullable', 'numeric'],
            'income_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'cogs_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'inventory_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'expense_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'sales_return_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'purchase_return_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'inventory_gain_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'inventory_loss_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'stock_transfer_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'wip_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'price_variance_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'is_active' => ['nullable', 'boolean'],
            'cost_price' => ['nullable', 'numeric'],
            'sales_price' => ['nullable', 'numeric'],
            'estimated_service_time_hours' => ['nullable', 'numeric'],
            'incentive_value' => ['nullable', 'numeric'],
            'minimum_stock' => ['nullable', 'numeric'],
            'maximum_stock' => ['nullable', 'numeric'],
            'reorder_point' => ['nullable', 'numeric'],
            'reorder_quantity' => ['nullable', 'numeric'],
            'safety_stock' => ['nullable', 'numeric'],
            'lead_time_days' => ['nullable', 'integer'],
            'review_period_days' => ['nullable', 'integer'],
            'auto_replenishment_enabled' => ['nullable', 'boolean'],
            'allow_auto_purchase_order' => ['nullable', 'boolean'],
        ];
    }
}
