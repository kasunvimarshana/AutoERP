<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;

final class RentalExpenseBillAllocationRequest extends TenantScopedRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->has('due_date') && ($this->input('due_date') === null || (is_string($this->input('due_date')) && trim($this->input('due_date')) === ''))) {
            $this->merge(['due_date' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'expected_allocation_version' => ['required', 'integer', 'min:'.AgreementFields::INITIAL_VERSION],
            'expected_agreement_version' => ['nullable', 'integer', 'min:'.AgreementFields::INITIAL_VERSION],
            'invoice_date' => ['required', 'date_format:'.AgreementFields::DATE_FORMAT],
            'due_date' => ['nullable', 'date_format:'.AgreementFields::DATE_FORMAT, 'after_or_equal:invoice_date'],
            'exchange_rate' => ['required', 'string', 'regex:'.AgreementFields::DECIMAL_PATTERN, 'numeric', 'gt:0'],
            'is_debit_note' => ['nullable', 'boolean'],
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
