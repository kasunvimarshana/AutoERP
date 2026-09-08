<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;

final class AgreementRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return ['expected_version' => ['sometimes', 'required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:'.AgreementFields::NOTES_LENGTH],
            'search' => ['nullable', 'string', 'max:'.AgreementFields::REFERENCE_LENGTH],
            'status' => ['prohibited'], 'row_version' => ['prohibited'], 'actor_id' => ['prohibited'], 'activated_at' => ['prohibited'], 'closed_at' => ['prohibited']];
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
            throw ValidationException::withMessages(['expected_version' => ['Reload the agreement and supply its current version.']]);
        }

        return (int) $this->validated('expected_version');
    }
}
