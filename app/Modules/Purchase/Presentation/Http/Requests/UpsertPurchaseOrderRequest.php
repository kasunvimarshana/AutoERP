<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;

final class UpsertPurchaseOrderRequest extends FormRequest
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
        $orderId = $this->route('purchaseOrder');
        $required = $this->isMethod('patch') ? ['sometimes'] : ['required'];

        return [
            'organization_unit_id' => ['sometimes', 'nullable', 'integer', Rule::exists('organization_units', 'id')->where('tenant_id', $tenantId)],
            'supplier_id' => [...$required, 'integer', Rule::exists('suppliers', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'warehouse_id' => [...$required, 'integer', Rule::exists('warehouses', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'po_number' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('purchase_orders', 'po_number')->where('tenant_id', $tenantId)->ignore($orderId)],
            'reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'order_date' => [...$required, 'date'],
            'expected_date' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'lines' => [...$required, 'array', 'min:1'],
            'lines.*.item_id' => ['required_with:lines', 'integer', Rule::exists('items', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'lines.*.uom_id' => ['required_with:lines', 'integer', Rule::exists('unit_of_measures', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'lines.*.description' => ['sometimes', 'nullable', 'string'],
            'lines.*.ordered_qty' => ['required_with:lines', 'numeric', 'gt:0', 'decimal:0,4'],
            'lines.*.unit_price' => ['required_with:lines', 'numeric', 'min:0', 'decimal:0,4'],
            'lines.*.discount_type' => ['sometimes', 'nullable', Rule::in(['percentage', 'fixed'])],
            'lines.*.discount_value' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'lines.*.discount_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
            'lines.*.tax_amount' => ['sometimes', 'numeric', 'min:0', 'decimal:0,4'],
        ];
    }
}
