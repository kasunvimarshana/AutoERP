<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;

final class OwnerDisbursementReportRequest extends TenantScopedRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $merges = [];
        foreach (['supplier_id', 'owner_agreement_id', 'wht_rate', 'report_type'] as $field) {
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
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'owner_agreement_id' => ['nullable', 'integer', 'min:1'],
            'wht_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'report_type' => ['nullable', 'string', 'in:summary,wht_ledger'],
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
