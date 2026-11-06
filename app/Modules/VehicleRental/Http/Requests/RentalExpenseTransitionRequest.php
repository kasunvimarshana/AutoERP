<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\RentalExpenseStatus;

final class RentalExpenseTransitionRequest extends TenantScopedRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->has('allocations') && is_array($this->input('allocations'))) {
            $normalized = [];
            foreach ($this->input('allocations') as $index => $row) {
                if (is_array($row)) {
                    foreach ([
                        'tax_amount',
                        'markup_amount',
                        'withholding_amount',
                        'target_customer_agreement_id',
                        'target_owner_agreement_id',
                        'target_vehicle_use_id',
                        'customer_id',
                        'supplier_id',
                        'employee_id',
                        'tax_group_id',
                    ] as $field) {
                        if (array_key_exists($field, $row) && is_string($row[$field]) && trim($row[$field]) === '') {
                            $row[$field] = null;
                        }
                    }
                    $normalized[$index] = $row;
                } else {
                    $normalized[$index] = $row;
                }
            }
            $this->merge(['allocations' => $normalized]);
        }
    }

    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::enum(RentalExpenseStatus::class)],
            'allocations' => ['nullable', 'array'],
            'allocations.*.allocation_type' => ['required_with:allocations', 'string'],
            'allocations.*.net_amount' => ['required_with:allocations', 'numeric'],
            'allocations.*.tax_amount' => ['nullable', 'numeric'],
            'allocations.*.markup_amount' => ['nullable', 'numeric'],
            'allocations.*.withholding_amount' => ['nullable', 'numeric'],
            'allocations.*.target_customer_agreement_id' => ['nullable', 'integer'],
            'allocations.*.target_owner_agreement_id' => ['nullable', 'integer'],
            'allocations.*.target_vehicle_use_id' => ['nullable', 'integer'],
            'allocations.*.customer_id' => ['nullable', 'integer'],
            'allocations.*.supplier_id' => ['nullable', 'integer'],
            'allocations.*.employee_id' => ['nullable', 'integer'],
            'allocations.*.tax_group_id' => ['nullable', 'integer'],
        ];
    }

    public function context(): AgreementContext
    {
        if ($this->organizationUnitId() === null || $this->currentUserId() === null) {
            throw ValidationException::withMessages(['context' => ['Select an organization and sign in before using Rental.']]);
        }

        return new AgreementContext($this->tenantId(), $this->organizationUnitId(), $this->currentUserId());
    }

    public function expectedVersion(): int
    {
        return (int) $this->validated('expected_version');
    }
}
