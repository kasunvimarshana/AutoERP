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
            'default_receipt_uom_id' => ['nullable', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'default_issue_uom_id' => ['nullable', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'default_consumption_uom_id' => ['nullable', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'default_charge_uom_id' => ['nullable', 'integer', 'min:1', 'exists:unit_of_measures,id'],
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
            'return_in_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'return_out_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'inventory_gain_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'inventory_loss_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'stock_transfer_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'wip_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'price_variance_account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
            'is_active' => ['nullable', 'boolean'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'maximum_stock' => ['nullable', 'numeric', 'min:0'],
            'reorder_point' => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'safety_stock' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'review_period_days' => ['nullable', 'integer', 'min:0'],
            'auto_replenishment_enabled' => ['nullable', 'boolean'],

            'attributes' => ['sometimes', 'array'],
            'attributes.*.id' => ['nullable', 'integer', 'min:1', 'exists:item_attributes,id'],
            'attributes.*.group_id' => ['nullable', 'integer', 'min:1', 'exists:item_attribute_groups,id'],
            'attributes.*.name' => ['required_with:attributes', 'string', 'max:255'],
            'attributes.*.type' => ['nullable', 'string', 'max:255'],
            'attributes.*.is_required' => ['nullable', 'boolean'],
            'attributes.*.display_order' => ['nullable', 'integer', 'min:0'],
            'attributes.*.metadata' => ['nullable', 'array'],
            'attributes.*.values' => ['nullable', 'array'],
            'attributes.*.values.*.value' => ['required_with:attributes.*.values', 'string', 'max:255'],
            'attributes.*.values.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'attributes.*.values.*.metadata' => ['nullable', 'array'],

            'variants' => ['sometimes', 'array'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.name' => ['required_with:variants', 'string', 'max:255'],
            'variants.*.is_default' => ['nullable', 'boolean'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'variants.*.metadata' => ['nullable', 'array'],
            'variants.*.attribute_value_ids' => ['nullable', 'array'],
            'variants.*.attribute_value_ids.*' => ['integer', 'min:1', 'exists:item_attribute_values,id'],

            'combo_items' => ['sometimes', 'array'],
            'combo_items.*.component_item_id' => ['required_with:combo_items', 'integer', 'min:1', 'exists:items,id'],
            'combo_items.*.component_variant_id' => ['nullable', 'integer', 'min:1', 'exists:item_variants,id'],
            'combo_items.*.quantity' => ['nullable', 'numeric', 'gt:0'],
            'combo_items.*.uom_id' => ['required_with:combo_items', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'combo_items.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'combo_items.*.metadata' => ['nullable', 'array'],

            'uom_conversions' => ['sometimes', 'array'],
            'uom_conversions.*.from_uom_id' => [
                'required_with:uom_conversions',
                'integer',
                'min:1',
                'exists:unit_of_measures,id',
            ],
            'uom_conversions.*.to_uom_id' => [
                'required_with:uom_conversions',
                'integer',
                'min:1',
                'exists:unit_of_measures,id',
            ],
            'uom_conversions.*.factor' => ['nullable', 'numeric', 'gt:0'],
            'uom_conversions.*.offset' => ['nullable', 'numeric'],
            'uom_conversions.*.is_bidirectional' => ['nullable', 'boolean'],
            'uom_conversions.*.is_active' => ['nullable', 'boolean'],
            'uom_conversions.*.metadata' => ['nullable', 'array'],

            'metadata_values' => ['sometimes', 'array'],
            'metadata_values.*.definition_id' => [
                'nullable',
                'integer',
                'min:1',
                'exists:item_metadata_definitions,id',
            ],
            'metadata_values.*.field_key' => ['nullable', 'string', 'max:100'],
            'metadata_values.*.label' => ['nullable', 'string', 'max:255'],
            'metadata_values.*.value_type' => ['nullable', 'string', 'in:string,number,boolean,date,datetime,json'],
            'metadata_values.*.is_required' => ['nullable', 'boolean'],
            'metadata_values.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'metadata_values.*.is_active' => ['nullable', 'boolean'],
            'metadata_values.*.metadata' => ['nullable', 'array'],
            'metadata_values.*.value' => ['nullable'],
        ];
    }
}
