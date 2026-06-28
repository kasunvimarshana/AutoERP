<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\Data\VersionedVehicleOwnershipCommand;

final class UpdateVehicleOwnershipRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'organization_unit_id' => ['nullable', 'integer'],
            'expected_version' => ['required', 'integer', 'min:1'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'vehicle_id' => ['prohibited'],
            'owner_type' => ['prohibited'],
            'owner_id' => ['prohibited'],
            'ownership_type' => ['prohibited'],
            'started_at' => ['prohibited'],
            'ended_at' => ['prohibited'],
            'is_current' => ['prohibited'],
        ];
    }

    public function toCommand(): VersionedVehicleOwnershipCommand
    {
        return new VersionedVehicleOwnershipCommand(
            expectedVersion: (int) $this->validated('expected_version'),
            notes: $this->validated('notes'),
        );
    }
}
