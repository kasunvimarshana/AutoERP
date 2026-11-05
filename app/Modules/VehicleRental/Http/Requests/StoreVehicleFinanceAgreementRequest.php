<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Data\AgreementContext;

final class StoreVehicleFinanceAgreementRequest extends TenantScopedRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $merges = [];
        foreach ([
            'agreement_number',
            'initial_deposit_amount',
            'residual_value',
            'interest_method',
            'payment_term_days',
            'tax_group_id',
            'remarks',
        ] as $field) {
            if ($this->has($field) && is_string($this->input($field)) && trim($this->input($field)) === '') {
                $merges[$field] = null;
            }
        }

        if ($this->has('schedule') && is_array($this->input('schedule'))) {
            $normalizedSchedule = [];
            foreach ($this->input('schedule') as $index => $row) {
                if (is_array($row)) {
                    foreach (['installment_number', 'fee_due', 'tax_due'] as $field) {
                        if (array_key_exists($field, $row) && is_string($row[$field]) && trim($row[$field]) === '') {
                            $row[$field] = null;
                        }
                    }
                    $normalizedSchedule[$index] = $row;
                } else {
                    $normalizedSchedule[$index] = $row;
                }
            }
            $merges['schedule'] = $normalizedSchedule;
        }

        if ($merges !== []) {
            $this->merge($merges);
        }
    }

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
            'schedule' => ['nullable', 'array'],
            'schedule.*.installment_number' => ['nullable', 'integer', 'min:1'],
            'schedule.*.due_date' => ['required_with:schedule', 'date'],
            'schedule.*.principal_due' => ['required_with:schedule', 'numeric', 'min:0'],
            'schedule.*.interest_due' => ['required_with:schedule', 'numeric', 'min:0'],
            'schedule.*.fee_due' => ['nullable', 'numeric', 'min:0'],
            'schedule.*.tax_due' => ['nullable', 'numeric', 'min:0'],
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
