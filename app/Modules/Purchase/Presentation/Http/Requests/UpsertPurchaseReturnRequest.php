<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertPurchaseReturnRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tenant_id' => $this->input('tenant_id', $this->attributes->get('current_tenant_id')),
            'organization_unit_id' => $this->input(
                'organization_unit_id',
                $this->attributes->get('current_organization_unit_id'),
            ),
            'created_by' => $this->input('created_by', $this->attributes->get('current_user_id')),
        ]);
    }

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
            'supplier_id' => array_merge($required, ['integer', 'min:1', 'exists:suppliers,id']),
            'original_purchase_order_id' => ['nullable', 'integer', 'min:1', 'exists:purchase_orders,id'],
            'original_grn_id' => ['nullable', 'integer', 'min:1', 'exists:grn_headers,id'],
            'original_invoice_id' => ['nullable', 'integer', 'min:1', 'exists:invoices,id'],
            'return_number' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'currency_id' => ['nullable', 'integer', 'min:1', 'exists:currencies,id'],
            'exchange_rate' => ['nullable', 'numeric'],
            'return_date' => array_merge($required, ['date']),
            'return_reason' => ['nullable', 'string', 'max:255'],
            'subtotal' => ['nullable', 'numeric'],
            'line_tax_total' => ['nullable', 'numeric'],
            'line_discount_total' => ['nullable', 'numeric'],
            'line_restocking_total' => ['nullable', 'numeric'],
            'header_discount_type' => ['nullable', 'string', 'max:255'],
            'header_discount_value' => ['nullable', 'numeric'],
            'header_discount_amount' => ['nullable', 'numeric'],
            'header_tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
            'header_tax_amount' => ['nullable', 'numeric'],
            'discount_total' => ['nullable', 'numeric'],
            'tax_total' => ['nullable', 'numeric'],
            'debit_note_total' => ['nullable', 'numeric'],
            'credit_note_total' => ['nullable', 'numeric'],
            'grand_total' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'integer', 'min:1'],
            'lines' => [$this->isMethod('post') ? 'required' : 'sometimes', 'array', 'min:1'],
            'lines.*.original_grn_line_id' => ['nullable', 'integer', 'min:1', 'exists:grn_lines,id'],
            'lines.*.original_purchase_order_line_id' => ['nullable', 'integer', 'min:1', 'exists:purchase_order_lines,id'],
            'lines.*.item_id' => ['required_with:lines', 'integer', 'min:1', 'exists:items,id'],
            'lines.*.variant_id' => ['nullable', 'integer', 'min:1', 'exists:item_variants,id'],
            'lines.*.batch_id' => ['nullable', 'integer', 'min:1', 'exists:batches,id'],
            'lines.*.serial_id' => ['nullable', 'integer', 'min:1', 'exists:serials,id'],
            'lines.*.warehouse_id' => ['nullable', 'integer', 'min:1', 'exists:warehouses,id'],
            'lines.*.location_id' => ['nullable', 'integer', 'min:1', 'exists:warehouse_locations,id'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.uom_id' => ['required_with:lines', 'integer', 'min:1', 'exists:unit_of_measures,id'],
            'lines.*.return_qty' => ['required_with:lines', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.restocking_fee' => ['nullable', 'numeric', 'min:0'],
            'lines.*.condition' => ['nullable', 'string', 'max:255'],
            'lines.*.disposition' => ['nullable', 'string', 'max:255'],
            'lines.*.quality_check_notes' => ['nullable', 'string'],
            'lines.*.discount_type' => ['nullable', 'in:percentage,fixed'],
            'lines.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_group_id' => ['nullable', 'integer', 'min:1', 'exists:tax_groups,id'],
            'lines.*.account_id' => ['nullable', 'integer', 'min:1', 'exists:accounts,id'],
        ];
    }
}
