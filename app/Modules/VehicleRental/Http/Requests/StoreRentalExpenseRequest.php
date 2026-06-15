<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalExpenseData;
use Modules\VehicleRental\Enums\RentalExpenseFinancialTreatment;
use Modules\VehicleRental\Enums\RentalExpenseType;

final class StoreRentalExpenseRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'usage_log_id' => ['nullable', 'integer', 'min:1'],
            'expense_type' => ['required', Rule::enum(RentalExpenseType::class)],
            'expense_date' => ['required', 'date'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'tax_group_id' => ['nullable', 'integer', 'min:1'],
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'original_net_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'original_tax_group_id' => ['nullable', 'integer', 'min:1'],
            'original_tax_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'original_gross_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'original_withholding_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'recoverable_input_tax_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'recovery_base_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'recovery_tax_group_id' => ['nullable', 'integer', 'min:1'],
            'recovery_tax_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'recovery_withholding_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'markup_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'financial_treatment' => ['required', Rule::enum(RentalExpenseFinancialTreatment::class)],
            'responsible_party_id' => ['nullable', 'integer', 'min:1'],
            'receipt_no' => ['nullable', 'string', 'max:100'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string', 'max:500'],
        ];
    }

    public function toData(): RentalExpenseData
    {
        return new RentalExpenseData(
            expenseType: RentalExpenseType::from((string) $this->input('expense_type')),
            expenseDate: (string) $this->input('expense_date'),
            amount: (string) $this->input('amount'),
            financialTreatment: RentalExpenseFinancialTreatment::from(
                (string) $this->input('financial_treatment'),
            ),
            usageLogId: $this->filled('usage_log_id') ? (int) $this->input('usage_log_id') : null,
            currencyId: $this->filled('currency_id') ? (int) $this->input('currency_id') : null,
            taxGroupId: $this->filled('tax_group_id') ? (int) $this->input('tax_group_id') : null,
            originalNetAmount: $this->filled('original_net_amount') ? (string) $this->input('original_net_amount') : null,
            originalTaxGroupId: $this->filled('original_tax_group_id') ? (int) $this->input('original_tax_group_id') : null,
            originalTaxAmount: (string) $this->input('original_tax_amount', '0.000000'),
            originalGrossAmount: $this->filled('original_gross_amount') ? (string) $this->input('original_gross_amount') : null,
            originalWithholdingAmount: (string) $this->input('original_withholding_amount', '0.000000'),
            recoverableInputTaxAmount: (string) $this->input('recoverable_input_tax_amount', '0.000000'),
            recoveryBaseAmount: $this->filled('recovery_base_amount') ? (string) $this->input('recovery_base_amount') : null,
            recoveryTaxGroupId: $this->filled('recovery_tax_group_id') ? (int) $this->input('recovery_tax_group_id') : null,
            recoveryTaxAmount: (string) $this->input('recovery_tax_amount', '0.000000'),
            recoveryWithholdingAmount: (string) $this->input('recovery_withholding_amount', '0.000000'),
            markupAmount: (string) $this->input('markup_amount', '0.000000'),
            responsiblePartyId: $this->filled('responsible_party_id')
                ? (int) $this->input('responsible_party_id')
                : null,
            receiptNo: $this->filled('receipt_no') ? (string) $this->input('receipt_no') : null,
            referenceNo: $this->filled('reference_no') ? (string) $this->input('reference_no') : null,
            description: $this->filled('description') ? (string) $this->input('description') : null,
            attachments: $this->input('attachments'),
            createdBy: $this->currentUserId(),
        );
    }
}
