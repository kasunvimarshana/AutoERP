<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\DTOs\VehicleOwnershipData;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Enums\VehicleOwnershipType;

final class SupersedeVehicleOwnershipRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => ['required', 'integer', 'min:1'],
            'correction_reason' => ['required', 'string', 'max:500'],
            'owner_type' => ['required', Rule::enum(VehicleOwnerType::class)],
            'owner_id' => ['required_unless:owner_type,company', 'prohibited_if:owner_type,company', 'nullable', 'integer', 'min:1'],
            'ownership_type' => ['required', Rule::enum(VehicleOwnershipType::class)],
            'started_at' => ['required', 'date'],
            'ended_at' => ['nullable', 'date', 'after:started_at'],
            'is_current' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function toData(): VehicleOwnershipData
    {
        return new VehicleOwnershipData(
            ownerType: VehicleOwnerType::from((string) $this->input('owner_type')),
            ownerId: $this->filled('owner_id') ? (int) $this->input('owner_id') : null,
            ownershipType: VehicleOwnershipType::from((string) $this->input('ownership_type')),
            startedAt: (string) $this->input('started_at'),
            endedAt: $this->filled('ended_at') ? (string) $this->input('ended_at') : null,
            isCurrent: (bool) $this->input('is_current', true),
            notes: $this->filled('notes') ? (string) $this->input('notes') : null,
        );
    }
}
