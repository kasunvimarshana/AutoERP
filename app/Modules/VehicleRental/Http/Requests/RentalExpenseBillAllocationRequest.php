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

        $merges = [];
        foreach (['due_date', 'expected_agreement_version', 'is_debit_note'] as $field) {
            if ($this->has($field) && ($this->input($field) === null || (is_string($this->input($field)) && trim($this->input($field)) === ''))) {
                $merges[$field] = null;
            }
        }

        if ($merges !== []) {
            $this->merge($merges);
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
