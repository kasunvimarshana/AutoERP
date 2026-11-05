<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Data\AgreementContext;

final class RefreshVehicleFinanceDueStatusesRequest extends TenantScopedRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->has('as_at') && is_string($this->input('as_at')) && trim($this->input('as_at')) === '') {
            $this->merge(['as_at' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'as_at' => ['nullable', 'date'],
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
