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
            'lines.*.discount_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'lines.*.tax_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
        ];
    }
}
