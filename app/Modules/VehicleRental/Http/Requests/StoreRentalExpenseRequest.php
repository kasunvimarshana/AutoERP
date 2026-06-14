<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalExpenseData;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
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
            'amount' => ['required', 'decimal:0,6', 'gt:0'],
            'is_billable' => ['required', 'boolean'],
            'receipt_no' => ['nullable', 'string', 'max:100'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string', 'max:500'],
            'status' => ['nullable', Rule::enum(RentalExpenseStatus::class)],
        ];
    }

    public function toData(): RentalExpenseData
    {
        return new RentalExpenseData(
            expenseType: RentalExpenseType::from((string) $this->input('expense_type')),
            amount: (string) $this->input('amount'),
            isBillable: $this->boolean('is_billable'),
            usageLogId: $this->filled('usage_log_id') ? (int) $this->input('usage_log_id') : null,
            receiptNo: $this->filled('receipt_no') ? (string) $this->input('receipt_no') : null,
            referenceNo: $this->filled('reference_no') ? (string) $this->input('reference_no') : null,
            description: $this->filled('description') ? (string) $this->input('description') : null,
            attachments: $this->input('attachments'),
            status: RentalExpenseStatus::from((string) $this->input('status', 'draft')),
            approvedBy: $this->currentUserId(),
        );
    }
}
