<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\Data\CreateVehicleOwnershipData;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Enums\VehicleOwnershipType;

final class StoreVehicleOwnershipRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'organization_unit_id' => ['nullable', 'integer'],
            'vehicle_id' => ['required', 'integer', 'min:1'],
            'owner_type' => ['required', Rule::enum(VehicleOwnerType::class)],
            'owner_id' => ['nullable', 'integer', 'min:1', 'required_unless:owner_type,company', 'prohibited_if:owner_type,company'],
            'ownership_type' => ['required', Rule::enum(VehicleOwnershipType::class)],
            'started_at' => ['required', 'date'],
            'ended_at' => ['nullable', 'date', 'after:started_at'],
            'is_current' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function toData(): CreateVehicleOwnershipData
    {
        return new CreateVehicleOwnershipData(
            vehicleId: (int) $this->validated('vehicle_id'),
            ownerType: VehicleOwnerType::from((string) $this->validated('owner_type')),
            ownerId: $this->validated('owner_id') === null ? null : (int) $this->validated('owner_id'),
            ownershipType: VehicleOwnershipType::from((string) $this->validated('ownership_type')),
            startedAt: (string) $this->validated('started_at'),
            endedAt: $this->validated('ended_at'),
            isCurrent: (bool) ($this->validated('is_current') ?? false),
            notes: $this->validated('notes'),
        );
    }
}
