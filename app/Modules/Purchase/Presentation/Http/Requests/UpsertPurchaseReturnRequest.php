<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;

final class UpsertPurchaseReturnRequest extends FormRequest
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
        $tenantId = app(CurrentTenantContextAccessorInterface::class)->currentTenantId();
        $returnId = $this->route('purchaseReturn');

        return [
            'organization_unit_id' => ['sometimes', 'nullable', 'integer', Rule::exists('organization_units', 'id')->where('tenant_id', $tenantId)],
            'original_grn_id' => ['required', 'integer', Rule::exists('grn_headers', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'original_invoice_id' => ['sometimes', 'nullable', 'integer', Rule::exists('invoices', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'return_number' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('purchase_returns', 'return_number')->where('tenant_id', $tenantId)->ignore($returnId)],
            'reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'return_date' => ['required', 'date'],
            'return_reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'header_discount_type' => ['sometimes', 'nullable', Rule::in(['percentage', 'fixed'])],
            'header_discount_value' => ['sometimes', 'nullable', 'numeric', 'min:0', 'decimal:0,4'],
            'header_discount_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'header_tax_group_id' => ['sometimes', 'nullable', 'integer', Rule::exists('tax_groups', 'id')->where('tenant_id', $tenantId)],
            'header_tax_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'header_charge_total' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'header_debit_adjustment_total' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'header_credit_adjustment_total' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'debit_note_total' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'credit_note_total' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.original_grn_line_id' => ['required', 'integer', Rule::exists('grn_lines', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'lines.*.item_id' => ['required', 'integer', Rule::exists('items', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'lines.*.uom_id' => ['required', 'integer', Rule::exists('unit_of_measures', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'lines.*.warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'lines.*.location_id' => ['sometimes', 'nullable', 'integer', Rule::exists('warehouse_locations', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'lines.*.description' => ['sometimes', 'nullable', 'string'],
            'lines.*.return_qty' => ['required', 'numeric', 'gt:0', 'decimal:0,4'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0', 'decimal:0,4'],
            'lines.*.restocking_fee' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'lines.*.condition' => ['sometimes', 'nullable', Rule::in(['good', 'damaged', 'expired', 'defective'])],
            'lines.*.disposition' => ['sometimes', 'nullable', Rule::in(['restock', 'scrap', 'return_to_vendor'])],
            'lines.*.discount_type' => ['sometimes', 'nullable', Rule::in(['percentage', 'fixed'])],
            'lines.*.discount_value' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'lines.*.discount_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'lines.*.tax_group_id' => ['sometimes', 'nullable', 'integer', Rule::exists('tax_groups', 'id')->where('tenant_id', $tenantId)],
            'lines.*.tax_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
        ];
    }
}
