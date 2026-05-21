<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => 'sometimes|nullable|integer|exists:tenants,id',
            'organization_unit_id' => 'sometimes|nullable|integer|exists:organization_units,id',

            'category_id' => 'required|integer|exists:categories,id',
            'brand_id' => 'required|integer|exists:brands,id',
            'type' => 'required|string',
            'name' => 'required|string',
            'slug' => 'required|string',
            'sku' => 'required|string',
            'description' => 'required|string',
            'image_file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'base_uom_id' => 'required|integer|exists:unit_of_measures,id',
            'purchase_uom_id' => 'required|integer|exists:unit_of_measures,id',
            'sales_uom_id' => 'required|integer|exists:unit_of_measures,id',
            'tax_group_id' => 'required|integer|exists:tax_groups,id',
            'is_batch_tracked' => 'required|boolean',
            'is_lot_tracked' => 'required|boolean',
            'is_serial_tracked' => 'required|boolean',
            'is_stockable' => 'required|boolean',
            'valuation_method' => 'required|string',
            'standard_cost' => 'required|numeric',
            'income_account_id' => 'required|integer|exists:accounts,id',
            'cogs_account_id' => 'required|integer|exists:accounts,id',
            'inventory_account_id' => 'required|integer|exists:accounts,id',
            'expense_account_id' => 'required|integer|exists:accounts,id',
            'is_active' => 'required|boolean',
            'cost_price' => 'required|numeric',
            'sales_price' => 'required|numeric',
            'estimated_service_time_hours' => 'required|numeric',
            'incentive_type' => 'required|string',
            'incentive_value' => 'required|numeric',

            //

            'combo_items' => 'nullable|array',
            'combo_items.*.combo_item_id' => 'nullable|integer|exists:items,id',
            'combo_items.*.component_item_id' => 'required|integer|exists:items,id',
            'combo_items.*.component_variant_id' => 'nullable|integer|exists:item_variants,id',
            'combo_items.*.sort_order' => 'required|integer',
            'combo_items.*.quantity' => 'required|integer',
            'combo_items.*.uom_id' => 'required|integer|exists:unit_of_measures,id',
            'combo_items.*.standard_cost' => 'required|numeric',
            'combo_items.*.cost_price' => 'required|string',
            'combo_items.*.sales_price' => 'required|boolean',
            'combo_items.*.incentive_type' => 'required|boolean',
            'combo_items.*.incentive_value' => 'required|boolean',

            //

            'uom_conversions' => 'nullable|array',
            'uom_conversions.*.from_uom_id' => 'required|integer|exists:unit_of_measures,id',
            'uom_conversions.*.to_uom_id' => 'required|integer|exists:unit_of_measures,id',
            'uom_conversions.*.factor' => 'required|numeric',
            'uom_conversions.*.item_id' => 'nullable|integer|exists:items,id',
            'uom_conversions.*.is_bidirectional' => 'required|boolean',
            'uom_conversions.*.is_active' => 'required|boolean',
        ];
    }
}
