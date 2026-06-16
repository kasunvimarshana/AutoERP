<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

final class FastPurchaseRequest extends PurchaseRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->isMethod('get')) {
            return array_merge($this->scopeRules(), [
                'search' => ['nullable', 'string', 'max:100'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            ]);
        }

        $rules = array_merge($this->scopeRules(), [
            'supplier_id' => ['required', 'integer', 'min:1'],
            'supplier_reference' => [$this->is('*/fast-purchases') ? 'required' : 'nullable', 'string', 'max:150'],
            'purchase_date' => ['required', 'date'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'due_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'options' => ['required', 'array'],
            'options.receive_stock_now' => ['required', 'boolean'],
            'options.create_supplier_invoice_now' => ['required', 'boolean'],
            'options.record_payment_now' => ['required', 'boolean'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'min:1'],
            'lines.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.uom_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.unit_cost' => ['nullable', 'decimal:0,6', 'gt:0'],
            'lines.*.discount_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.tax_group_id' => ['nullable', 'integer', 'min:1'],
            'payment' => ['nullable', 'array'],
            'payment.amount' => ['nullable', 'decimal:0,6', 'gt:0'],
            'payment.payment_method_id' => ['nullable', 'integer', 'min:1'],
            'payment.source_account_id' => ['nullable', 'integer', 'min:1'],
            'payment.reference' => ['nullable', 'string', 'max:150'],
            'payment.cheque_number' => ['nullable', 'string', 'max:100'],
            'payment.cheque_date' => ['nullable', 'date'],
            'payment.card_reference' => ['nullable', 'string', 'max:100'],
            'payment.instrument_number' => ['nullable', 'string', 'max:100'],
            'payment.instrument_date' => ['nullable', 'date'],
            'payment.external_bank_name' => ['nullable', 'string', 'max:150'],
            'payment.external_bank_branch' => ['nullable', 'string', 'max:150'],
            'payment.lines' => ['nullable', 'array'],
            'payment.lines.*.amount' => ['required_with:payment.lines', 'decimal:0,6', 'gt:0'],
            'payment.lines.*.payment_method_id' => ['nullable', 'integer', 'min:1'],
            'payment.lines.*.source_account_id' => ['nullable', 'integer', 'min:1'],
            'payment.lines.*.reference' => ['nullable', 'string', 'max:150'],
            'payment.lines.*.instrument_number' => ['nullable', 'string', 'max:100'],
            'payment.lines.*.instrument_date' => ['nullable', 'date'],
            'payment.lines.*.external_bank_name' => ['nullable', 'string', 'max:150'],
            'payment.lines.*.external_bank_branch' => ['nullable', 'string', 'max:150'],
        ]);

        foreach ($this->clientAuthorityFields() as $field) {
            $rules[$field] = ['prohibited'];
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return array_merge($this->validated(), [
            'tenant_id' => $this->tenantId(),
            'organization_unit_id' => $this->organizationUnitId(),
            'current_user_id' => $this->currentUserId(),
        ]);
    }

    /**
     * @return list<string>
     */
    private function clientAuthorityFields(): array
    {
        return [
            'subtotal',
            'discount_total',
            'tax_total',
            'withholding_total',
            'grand_total',
            'paid_total',
            'balance_due',
            'status',
            'posting_status',
            'approval_status',
            'finance_account_id',
            'payable_account_id',
            'inventory_account_id',
            'base_quantity',
            'base_uom_quantity',
            'lines.*.line_total',
            'lines.*.tax_amount',
            'lines.*.withholding_amount',
            'lines.*.base_quantity',
            'lines.*.base_uom_quantity',
            'lines.*.finance_account_id',
            'lines.*.status',
            'payment.status',
            'payment.posting_status',
            'payment.payable_balance',
            'payment.finance_account_id',
            'payment.lines.*.status',
            'payment.lines.*.finance_account_id',
        ];
    }
}
