<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;
use Modules\VehicleRental\Enums\RentalExpenseType;

final class StoreRentalExpenseRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'agreement_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_allocation_id' => ['nullable', 'integer', 'min:1'],
            'usage_log_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_id' => ['required', 'integer', 'min:1'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
            'expense_type' => ['required', Rule::enum(RentalExpenseType::class)],
            'expense_date' => ['required', 'date'],
            'currency_id' => ['required', 'integer', 'min:1'],
            'net_amount' => ['required', 'decimal:0,6', 'gt:0'],
            'tax_group_id' => ['nullable', 'integer', 'min:1'],
            'tax_amount' => ['nullable', 'decimal:0,6', 'gte:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'source_document_type' => ['nullable', 'string', 'max:80'],
            'source_document_id' => ['nullable', 'integer', 'min:1'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.allocation_type' => ['required', Rule::enum(RentalExpenseAllocationType::class)],
            'allocations.*.target_agreement_id' => ['nullable', 'integer', 'min:1'],
            'allocations.*.target_vehicle_allocation_id' => ['nullable', 'integer', 'min:1'],
            'allocations.*.customer_id' => ['nullable', 'integer', 'min:1'],
            'allocations.*.supplier_id' => ['nullable', 'integer', 'min:1'],
            'allocations.*.employee_id' => ['nullable', 'integer', 'min:1'],
            'allocations.*.net_amount' => ['required', 'decimal:0,6', 'gte:0'],
            'allocations.*.tax_group_id' => ['nullable', 'integer', 'min:1'],
            'allocations.*.tax_amount' => ['nullable', 'decimal:0,6', 'gte:0'],
            'allocations.*.withholding_amount' => ['nullable', 'decimal:0,6', 'gte:0'],
            'allocations.*.markup_amount' => ['nullable', 'decimal:0,6', 'gte:0'],
        ];
    }
}
