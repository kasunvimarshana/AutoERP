<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertPurchaseOrderLineRequest extends FormRequest
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
            'purchase_order_id' => array_merge($required, ['integer', 'min:1', 'exists:purchase_orders,id']),
            'item_id' => array_merge($required, ['integer', 'min:1', 'exists:items,id']),
            'variant_id' => ['nullable', 'integer', 'min:1', 'exists:item_variants,id'],
            'description' => ['nullable', 'string'],
            'uom_id' => array_merge($required, ['integer', 'min:1', 'exists:unit_of_measures,id']),
            'ordered_qty' => array_merge($required, ['numeric']),
            'received_qty' => ['nullable', 'numeric'],
            'rejected_qty' => ['nullable', 'numeric'],
            'invoiced_qty' => ['nullable', 'numeric'],
            'unit_price' => array_merge($required, ['numeric']),
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
}