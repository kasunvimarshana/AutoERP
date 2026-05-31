<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Item\Application\Support\ItemUomOptions;

final class UpsertPurchaseReturnLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'reference' => ['nullable', 'string', 'max:255'],
            'purchase_return_id' => array_merge($required, ['integer', 'min:1', 'exists:purchase_returns,id']),
            'original_grn_line_id' => ['nullable', 'integer', 'min:1', 'exists:grn_lines,id'],
            'original_purchase_order_line_id' => ['nullable', 'integer', 'min:1', 'exists:purchase_order_lines,id'],
            'original_document_line_id' => ['nullable', 'integer', 'min:1', 'exists:document_items,id'],
            'item_id' => array_merge($required, ['integer', 'min:1', 'exists:items,id']),
            'variant_id' => ['nullable', 'integer', 'min:1', 'exists:item_variants,id'],
            'batch_id' => ['nullable', 'integer', 'min:1', 'exists:batches,id'],
            'serial_id' => ['nullable', 'integer', 'min:1', 'exists:serials,id'],
            'warehouse_id' => ['nullable', 'integer', 'min:1', 'exists:warehouses,id'],
            'location_id' => ['nullable', 'integer', 'min:1', 'exists:warehouse_locations,id'],
            'description' => ['nullable', 'string'],
            'uom_id' => array_merge($required, ['integer', 'min:1', 'exists:unit_of_measures,id']),
            'return_qty' => array_merge($required, ['numeric']),
            'unit_price' => array_merge($required, ['numeric']),
            'restocking_fee' => ['nullable', 'numeric'],
            'condition' => ['nullable', 'string', 'max:255'],
            'disposition' => ['nullable', 'string', 'max:255'],
            'quality_check_notes' => ['nullable', 'string'],
            'discount_type' => ['nullable', 'string', 'max:255'],
            'discount_value' => ['nullable', 'numeric'],
            'discount_amount' => ['nullable', 'numeric'],
            'gross_amount' => ['nullable', 'numeric'],
            'line_total' => ['nullable', 'numeric'],
            'tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
            'tax_amount' => ['nullable', 'numeric'],
            'line_total_with_tax' => ['nullable', 'numeric'],
            'account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id']
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tenantId = (int) $this->input('tenant_id');
            $itemId = $this->input('item_id');
            $uomId = $this->input('uom_id');

            if ($tenantId > 0 && is_numeric($itemId) && is_numeric($uomId) && ! ItemUomOptions::isAllowed($tenantId, (int) $itemId, (int) $uomId, 'purchase')) {
                $validator->errors()->add('uom_id', 'The selected UOM is not configured for this item in purchase context.');
            }
        });
    }
}
