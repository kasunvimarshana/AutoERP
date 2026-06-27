<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

final class FastSalesRequest extends SalesRequest
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
            'customer_id' => ['required', 'integer', 'min:1'],
            'idempotency_key' => ['nullable', 'string', 'max:150'],
            'customer_reference' => [$this->is('*/fast-sales') ? 'required' : 'nullable', 'string', 'max:150'],
            'transaction_date' => ['required', 'date'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'due_date' => ['nullable', 'date', 'after_or_equal:transaction_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'options' => ['required', 'array'],
            'options.create_sales_order_only' => ['required', 'boolean'],
            'options.deliver_items_now' => ['required', 'boolean'],
            'options.create_customer_invoice_now' => ['required', 'boolean'],
            'options.record_customer_receipt_now' => ['required', 'boolean'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'min:1'],
            'lines.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.uom_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.unit_price' => ['nullable', 'decimal:0,6', 'gt:0'],
            'lines.*.discount_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.tax_group_id' => ['nullable', 'integer', 'min:1'],
            'payment' => ['nullable', 'array'],
            'payment.amount' => ['nullable', 'decimal:0,6', 'gt:0'],
            'payment.payment_method_id' => ['required_with:payment.amount', 'integer', 'min:1'],
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
            'payment.lines.*.payment_method_id' => ['required_with:payment.lines', 'integer', 'min:1'],
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
            'idempotency_key' => $this->input('idempotency_key') ?: $this->header('Idempotency-Key'),
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
            'received_total',
            'balance_due',
            'status',
            'posting_status',
            'approval_status',
            'finance_account_id',
            'receivable_account_id',
            'revenue_account_id',
            'inventory_account_id',
            'cost_of_goods_sold_account_id',
            'base_quantity',
            'base_uom_quantity',
            'available_stock',
            'available_quantity',
            'lines.*.line_total',
            'lines.*.tax_amount',
            'lines.*.withholding_amount',
            'lines.*.base_quantity',
            'lines.*.base_uom_quantity',
            'lines.*.available_stock',
            'lines.*.available_quantity',
            'lines.*.finance_account_id',
            'lines.*.status',
            'lines.*.source_line_type',
            'lines.*.source_line_id',
            'payment.destination_account_id',
            'payment.lines.*.destination_account_id',
            'payment.status',
            'payment.posting_status',
            'payment.outstanding_balance',
            'payment.finance_account_id',
            'payment.direction',
            'payment.lines.*.status',
            'payment.lines.*.finance_account_id',
        ];
    }
}
