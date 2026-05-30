<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListItemRequest extends FormRequest
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
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('item.pagination.max_per_page', 200)],
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'brand_id' => ['nullable', 'integer', 'min:1'],
            'item_type_id' => ['nullable', 'integer', 'min:1'],
            'type' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'base_uom_id' => ['nullable', 'integer', 'min:1'],
            'default_receipt_uom_id' => ['nullable', 'integer', 'min:1'],
            'default_issue_uom_id' => ['nullable', 'integer', 'min:1'],
            'default_consumption_uom_id' => ['nullable', 'integer', 'min:1'],
            'default_charge_uom_id' => ['nullable', 'integer', 'min:1'],
            'tax_group_id' => ['nullable', 'integer', 'min:1'],
            'default_currency_id' => ['nullable', 'integer', 'min:1'],
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
            'income_account_id' => ['nullable', 'integer', 'min:1'],
            'cogs_account_id' => ['nullable', 'integer', 'min:1'],
            'inventory_account_id' => ['nullable', 'integer', 'min:1'],
            'expense_account_id' => ['nullable', 'integer', 'min:1'],
            'return_in_account_id' => ['nullable', 'integer', 'min:1'],
            'return_out_account_id' => ['nullable', 'integer', 'min:1'],
            'inventory_gain_account_id' => ['nullable', 'integer', 'min:1'],
            'inventory_loss_account_id' => ['nullable', 'integer', 'min:1'],
            'stock_transfer_account_id' => ['nullable', 'integer', 'min:1'],
            'wip_account_id' => ['nullable', 'integer', 'min:1'],
            'price_variance_account_id' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'auto_replenishment_enabled' => ['nullable', 'boolean'],
        ];
    }
}
