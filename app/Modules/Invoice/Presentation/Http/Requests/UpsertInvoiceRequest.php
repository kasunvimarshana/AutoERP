<?php

declare(strict_types=1);

namespace Modules\Invoice\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;

final class UpsertInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $tenantId = app(CurrentTenantContextAccessorInterface::class)->currentTenantId();
        $invoiceId = $this->route('invoice');
        $required = $this->isMethod('patch') ? ['sometimes'] : ['required'];

        return [
            'organization_unit_id' => ['sometimes', 'nullable', 'integer', Rule::exists('organization_units', 'id')->where('tenant_id', $tenantId)],
            'invoice_number' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('invoices', 'invoice_number')->where('tenant_id', $tenantId)->ignore($invoiceId)],
            'external_reference_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'document_type' => ['sometimes', Rule::in(['invoice', 'debit_adjustment', 'credit_adjustment', 'refund', 'reversal', 'write_off'])],
            'business_context' => ['sometimes', 'string', 'max:50'],
            'ledger_direction' => [...$required, Rule::in(['receivable', 'payable'])],
            'balance_effect' => ['sometimes', Rule::in(['increase', 'decrease', 'none'])],
            'customer_id' => ['sometimes', 'nullable', 'integer', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'supplier_id' => ['sometimes', 'nullable', 'integer', Rule::exists('suppliers', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'currency_id' => ['sometimes', 'nullable', 'integer', Rule::exists('currencies', 'id')],
            'exchange_rate' => ['sometimes', 'numeric', 'gt:0'],
            'invoice_date' => [...$required, 'date'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'original_invoice_id' => ['sometimes', 'nullable', 'integer', Rule::exists('invoices', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'reversal_of_invoice_id' => ['sometimes', 'nullable', 'integer', Rule::exists('invoices', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'reason_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'reason' => ['sometimes', 'nullable', 'string'],
            'rounding_adjustment' => ['sometimes', 'numeric'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'terms' => ['sometimes', 'nullable', 'string'],
            'lines' => [...$required, 'array', 'min:1'],
            'lines.*.line_no' => ['sometimes', 'integer', 'min:1'],
            'lines.*.line_type' => ['sometimes', 'string', 'max:50'],
            'lines.*.item_id' => ['sometimes', 'nullable', 'integer', Rule::exists('items', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'lines.*.uom_id' => ['sometimes', 'nullable', 'integer', Rule::exists('unit_of_measures', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'lines.*.item_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'lines.*.item_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'lines.*.uom_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'lines.*.description' => ['sometimes', 'nullable', 'string'],
            'lines.*.quantity' => ['required_with:lines', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.discount_total' => ['sometimes', 'numeric', 'min:0'],
            'lines.*.tax_total' => ['sometimes', 'numeric', 'min:0'],
            'lines.*.charge_total' => ['sometimes', 'numeric', 'min:0'],
            'adjustments' => ['sometimes', 'array'],
            'adjustments.*.level' => ['sometimes', Rule::in(['header', 'line'])],
            'adjustments.*.effect' => ['required_with:adjustments', Rule::in(['add', 'deduct', 'subtract'])],
            'adjustments.*.adjustment_type' => ['required_with:adjustments', 'string', 'max:50'],
            'adjustments.*.amount' => ['required_with:adjustments', 'numeric', 'min:0'],
            'adjustments.*.code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'adjustments.*.name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'adjustments.*.rate' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
