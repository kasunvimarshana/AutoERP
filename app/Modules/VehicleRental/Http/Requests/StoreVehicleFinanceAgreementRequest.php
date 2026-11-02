<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Data\AgreementContext;

final class StoreVehicleFinanceAgreementRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'agreement_number' => ['nullable', 'string', 'max:100'],
            'supplier_id' => ['required', 'integer'],
            'vehicle_id' => ['required', 'integer'],
            'agreement_date' => ['required', 'date'],
            'starts_at' => ['required', 'date'],
            'matures_at' => ['required', 'date', 'after:starts_at'],
            'currency_id' => ['required', 'integer'],
            'principal_amount' => ['required', 'numeric', 'min:0.01'],
            'initial_deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'residual_value' => ['nullable', 'numeric', 'min:0'],
            'interest_method' => ['nullable', 'string', Rule::in(['flat', 'reducing'])],
            'annual_interest_rate' => ['required', 'numeric', 'min:0'],
            'installment_frequency' => ['required', 'string', Rule::in(['weekly', 'monthly', 'quarterly', 'yearly'])],
            'installment_count' => ['required', 'integer', 'min:1'],
            'payment_term_days' => ['nullable', 'integer', 'min:0'],
            'tax_group_id' => ['nullable', 'integer'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function context(): AgreementContext
    {
        if ($this->organizationUnitId() === null || $this->currentUserId() === null) {
            throw ValidationException::withMessages(['context' => ['Select an organization and sign in before using Rental.']]);
        }

        return new AgreementContext($this->tenantId(), $this->organizationUnitId(), $this->currentUserId());
    }
}
