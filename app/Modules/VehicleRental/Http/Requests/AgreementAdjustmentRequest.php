<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementAdjustmentCategory;
use Modules\VehicleRental\Enums\AgreementAdjustmentStatus;
use Modules\VehicleRental\Enums\AgreementAdjustmentType;

final class AgreementAdjustmentRequest extends TenantScopedRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $merges = [];
        foreach (['reason', 'search', 'category', 'adjustment_type', 'status', 'kind'] as $field) {
            if ($this->has($field) && is_string($this->input($field)) && trim($this->input($field)) === '') {
                $merges[$field] = null;
            }
        }

        if ($merges !== []) {
            $this->merge($merges);
        }
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return [
                'agreement_id' => ['nullable', 'integer', 'min:1'],
                'kind' => ['nullable', 'string', 'in:customer,owner'],
                'status' => ['nullable', 'string', Rule::enum(AgreementAdjustmentStatus::class)],
                'search' => ['nullable', 'string', 'max:255'],
            ];
        }

        if ($this->isMethod('POST') && str_ends_with($this->path(), 'void')) {
            return [
                'expected_version' => ['required', 'integer', 'min:1'],
                'reason' => ['required', 'string', 'min:3', 'max:1000'],
            ];
        }

        return [
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'customer_agreement_id' => ['required_without:owner_agreement_id', 'nullable', 'integer', 'min:1', 'prohibits:owner_agreement_id'],
            'owner_agreement_id' => ['required_without:customer_agreement_id', 'nullable', 'integer', 'min:1', 'prohibits:customer_agreement_id'],
            'invoice_id' => ['nullable', 'integer', 'min:1'],
            'adjustment_type' => ['required', Rule::enum(AgreementAdjustmentType::class)],
            'category' => ['required', Rule::enum(AgreementAdjustmentCategory::class)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
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
        if (! $this->filled('expected_version')) {
            throw ValidationException::withMessages(['expected_version' => ['Reload the record and supply its current version.']]);
        }

        return (int) $this->input('expected_version');
    }
}
