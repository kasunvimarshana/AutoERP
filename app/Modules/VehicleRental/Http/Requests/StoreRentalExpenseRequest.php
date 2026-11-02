<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\RentalExpenseType;

final class StoreRentalExpenseRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'expense_number' => ['nullable', 'string', 'max:100'],
            'customer_agreement_id' => ['nullable', 'integer'],
            'vehicle_use_id' => ['nullable', 'integer'],
            'running_chart_id' => ['nullable', 'integer'],
            'vehicle_id' => ['required', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
            'employee_id' => ['nullable', 'integer'],
            'expense_type' => ['required', Rule::enum(RentalExpenseType::class)],
            'expense_date' => ['required', 'date'],
            'currency_id' => ['required', 'integer'],
            'net_amount' => ['required', 'numeric', 'min:0.01'],
            'tax_group_id' => ['nullable', 'integer'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
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
