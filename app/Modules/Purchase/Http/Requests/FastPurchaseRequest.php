<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Purchase\Enums\PurchaseAdjustmentAllocationMethod;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationBase;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationType;
use Modules\Purchase\Enums\PurchaseAdjustmentEffect;
use Modules\Purchase\Enums\PurchaseAdjustmentType;

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
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'payment_terms' => ['nullable', 'string', Rule::in(['due_on_receipt', 'net_7', 'net_15', 'net_30', 'explicit_due_date'])],
            'due_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'options' => ['required', 'array'],
            'options.receive_stock_now' => ['required', 'boolean'],
            'options.create_supplier_invoice_now' => ['required', 'boolean'],
            'options.record_payment_now' => ['required', 'boolean'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.client_line_key' => ['required', 'string', 'max:100', 'distinct'],
            'lines.*.item_id' => ['required', 'integer', 'min:1'],
            'lines.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.uom_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.pricing_mode' => ['required', Rule::in(['auto', 'manual'])],
            'lines.*.unit_cost' => ['nullable', 'decimal:0,6', 'gt:0'],
            'lines.*.manual_price_confirmed' => ['nullable', 'boolean'],
            'lines.*.pricing_context_hash' => ['nullable', 'string', 'size:64'],
            'lines.*.discount_calculation_type' => ['nullable', Rule::enum(PurchaseAdjustmentCalculationType::class)],
            'lines.*.discount_rate' => ['nullable', 'decimal:0,6', 'min:0', 'max:100'],
            'lines.*.discount_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.tax_group_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.charge_calculation_type' => ['nullable', Rule::enum(PurchaseAdjustmentCalculationType::class)],
            'lines.*.charge_rate' => ['nullable', 'decimal:0,6', 'min:0', 'max:100'],
            'lines.*.charge_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'adjustments' => ['nullable', 'array'],
            'adjustments.*.name' => ['required', 'string', 'max:255'],
            'adjustments.*.adjustment_type' => ['required', Rule::enum(PurchaseAdjustmentType::class)],
            'adjustments.*.effect' => ['required', Rule::enum(PurchaseAdjustmentEffect::class)],
            'adjustments.*.calculation_type' => ['nullable', Rule::enum(PurchaseAdjustmentCalculationType::class)],
            'adjustments.*.calculation_base' => ['nullable', Rule::enum(PurchaseAdjustmentCalculationBase::class)],
            'adjustments.*.rate' => ['nullable', 'decimal:0,6', 'min:0', 'max:100'],
            'adjustments.*.amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'adjustments.*.allocation_method' => ['nullable', Rule::enum(PurchaseAdjustmentAllocationMethod::class)],
            'adjustments.*.is_allocatable' => ['nullable', 'boolean'],
            'adjustments.*.description' => ['nullable', 'string'],
            'adjustments.*.allocations' => ['nullable', 'array'],
            'adjustments.*.allocations.*.client_line_key' => ['required_with:adjustments.*.allocations', 'string', 'max:100'],
            'adjustments.*.allocations.*.amount' => ['required_with:adjustments.*.allocations', 'decimal:0,6', 'min:0'],
            'payment' => ['nullable', 'array'],
            'payment.amount' => ['nullable', 'decimal:0,6', 'gt:0'],
            'payment.payment_method_id' => ['nullable', 'integer', 'min:1'],
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
            'lines.*.line_subtotal',
            'lines.*.tax_calculation_type',
            'lines.*.tax_rate',
            'lines.*.tax_amount',
            'lines.*.withholding_amount',
            'lines.*.base_quantity',
            'lines.*.base_uom_quantity',
            'lines.*.finance_account_id',
            'lines.*.status',
            'adjustments.*.finance_posting_profile_id',
            'adjustments.*.finance_account_id',
            'adjustments.*.cost_treatment',
            'adjustments.*.tax_treatment',
            'adjustments.*.mapping_source',
            'adjustments.*.override_reason',
            'payment.status',
            'payment.posting_status',
            'payment.payable_balance',
            'payment.finance_account_id',
            'payment.source_account_id',
            'payment.lines.*.status',
            'payment.lines.*.finance_account_id',
            'payment.lines.*.source_account_id',
        ];
    }
}
