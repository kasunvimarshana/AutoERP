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
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Constants\IncidentFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\IncidentStatus;
use Modules\VehicleRental\Enums\IncidentType;

final class RentalIncidentRequest extends TenantScopedRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $merges = [];
        foreach (['search', 'status', 'incident_type', 'reason', 'evidence_reference', 'description', 'running_chart_id'] as $field) {
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
                'search' => ['nullable', 'string', 'max:255'],
                'status' => ['nullable', 'string', Rule::enum(IncidentStatus::class)],
                'incident_type' => ['nullable', 'string', Rule::enum(IncidentType::class)],
            ];
        }

        if ($this->route('action') !== null) {
            return [
                'expected_version' => ['required', 'integer', 'min:1'],
                'reason' => ['required', 'string', 'max:'.IncidentFields::REVIEW_REASON_LENGTH],
            ];
        }

        if ($this->is('*/attachments*') || $this->hasFile('file')) {
            return [
                'expected_version' => ['required', 'integer', 'min:1'],
                'file' => ['required', 'file'],
            ];
        }

        return [
            'vehicle_use_id' => ['required', 'integer', 'min:1'],
            'expected_use_version' => ['required', 'integer', 'min:1'],
            'running_chart_id' => ['nullable', 'integer', 'min:1'],
            'incident_type' => ['required', Rule::enum(IncidentType::class)],
            'occurred_on' => ['required', 'date_format:'.AgreementFields::DATE_FORMAT],
            'evidence_reference' => ['required', 'string', 'max:'.IncidentFields::EVIDENCE_REFERENCE_LENGTH],
            'description' => ['required', 'string', 'max:'.IncidentFields::DESCRIPTION_LENGTH],
        ];
    }

    public function context(): AgreementContext
    {
        if ($this->organizationUnitId() === null || $this->currentUserId() === null) {
            throw ValidationException::withMessages(['context' => ['Select an organization and sign in before using Rental.']]);
        }

        return new AgreementContext($this->tenantId(), (int) $this->organizationUnitId(), (int) $this->currentUserId());
    }

    public function expectedVersion(): int
    {
        if (! $this->filled('expected_version')) {
            throw ValidationException::withMessages(['expected_version' => ['Reload the record and supply its current version.']]);
        }

        return (int) $this->input('expected_version');
    }
}
