<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Enums\VehicleFinanceInstallmentFrequency;
use Modules\VehicleRental\Enums\VehicleFinanceInterestMethod;

final class StoreVehicleFinanceAgreementRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'agreement_number' => ['nullable', 'string', 'max:100'],
            'supplier_id' => ['required', 'integer', 'min:1'],
            'vehicle_id' => ['required', 'integer', 'min:1'],
            'agreement_date' => ['required', 'date'],
            'starts_at' => ['required', 'date'],
            'matures_at' => ['required', 'date', 'after:starts_at'],
            'currency_id' => ['required', 'integer', 'min:1'],
            'principal_amount' => ['required', 'decimal:0,6', 'gt:0'],
            'initial_deposit_amount' => ['required', 'decimal:0,6', 'gte:0'],
            'residual_value' => ['required', 'decimal:0,6', 'gte:0'],
            'interest_method' => ['required', Rule::enum(VehicleFinanceInterestMethod::class)],
            'annual_interest_rate' => ['required', 'decimal:0,6', 'gte:0'],
            'installment_frequency' => ['required', Rule::enum(VehicleFinanceInstallmentFrequency::class)],
            'installment_count' => ['required', 'integer', 'between:1,600'],
            'payment_term_days' => ['required', 'integer', 'between:0,3650'],
            'tax_group_id' => ['nullable', 'integer', 'min:1'],
            'remarks' => ['nullable', 'string'],
            'schedule' => [
                'required_if:interest_method,'.VehicleFinanceInterestMethod::Custom->value,
                'prohibited_unless:interest_method,'.VehicleFinanceInterestMethod::Custom->value,
                'array',
                'min:1',
            ],
            'schedule.*.installment_number' => ['nullable', 'integer', 'min:1'],
            'schedule.*.due_date' => ['required_with:schedule', 'date'],
            'schedule.*.principal_due' => ['required_with:schedule', 'decimal:0,6', 'gte:0'],
            'schedule.*.interest_due' => ['nullable', 'decimal:0,6', 'gte:0'],
            'schedule.*.fee_due' => ['nullable', 'decimal:0,6', 'gte:0'],
            'schedule.*.tax_due' => ['nullable', 'decimal:0,6', 'gte:0'],
        ];
    }
}
