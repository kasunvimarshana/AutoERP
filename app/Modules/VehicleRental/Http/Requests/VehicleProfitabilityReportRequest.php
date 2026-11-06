<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;

final class VehicleProfitabilityReportRequest extends TenantScopedRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $merges = [];
        foreach (['ownership_type', 'vehicle_id'] as $field) {
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
        return [
            'start_date' => ['required', 'date_format:'.AgreementFields::DATE_FORMAT],
            'end_date' => ['required', 'date_format:'.AgreementFields::DATE_FORMAT, 'after_or_equal:start_date'],
            'ownership_type' => ['nullable', 'string', 'in:all,company,owner_leased'],
            'vehicle_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function context(): AgreementContext
    {
        if ($this->organizationUnitId() === null || $this->currentUserId() === null) {
            throw ValidationException::withMessages(['context' => ['Select an organization and sign in before using Rental.']]);
        }

        return new AgreementContext($this->tenantId(), (int) $this->organizationUnitId(), (int) $this->currentUserId());
    }
}
