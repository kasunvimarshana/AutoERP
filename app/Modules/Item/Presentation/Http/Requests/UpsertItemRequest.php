<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'category_id' => ['nullable', 'integer', 'min:1', 'exists:item_categories,id'],
            'brand_id' => ['nullable', 'integer', 'min:1', 'exists:item_brands,id'],
            'item_type_id' => ['nullable', 'integer', 'min:1', 'exists:item_types,id'],
            'type' => ['nullable', 'string', 'max:255'],
            'name' => array_merge($required, ['string', 'max:255']),
            'slug' => ['nullable', 'string', 'max:255'],
            'sku' => array_merge($required, ['string', 'max:255']),
            'barcode' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'base_uom_id' => array_merge($required, ['integer', 'min:1', 'exists:unit_of_measures,id']),
            'purchase_uom_id' => ['nullable', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'sales_uom_id' => ['nullable', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
            'default_currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'is_batch_tracked' => ['nullable', 'boolean'],
            'is_lot_tracked' => ['nullable', 'boolean'],
            'is_serial_tracked' => ['nullable', 'boolean'],
            'is_stockable' => ['nullable', 'boolean'],
            'is_purchasable' => ['nullable', 'boolean'],
            'is_sellable' => ['nullable', 'boolean'],
            'is_service' => ['nullable', 'boolean'],
            'is_rentable' => ['nullable', 'boolean'],
            'is_chargeable' => ['nullable', 'boolean'],
            'is_taxable' => ['nullable', 'boolean'],
            'is_variable' => ['nullable', 'boolean'],
            'standard_cost' => ['nullable', 'numeric', 'min:0'],
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
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sales_price' => ['nullable', 'numeric', 'min:0'],
            'estimated_service_time_hours' => ['nullable', 'numeric', 'min:0'],
            'incentive_value' => ['nullable', 'numeric', 'min:0'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'maximum_stock' => ['nullable', 'numeric', 'min:0'],
            'reorder_point' => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'safety_stock' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'review_period_days' => ['nullable', 'integer', 'min:0'],
            'auto_replenishment_enabled' => ['nullable', 'boolean'],
            'allow_auto_purchase_order' => ['nullable', 'boolean'],
        ];
    }
}
