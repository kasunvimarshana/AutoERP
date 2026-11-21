<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\DTOs\VehicleServiceJobTypeData;

final class UpdateVehicleServiceJobTypeRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => ['required', 'integer', 'min:1'],
            'code' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'tracks_mileage' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function toData(): VehicleServiceJobTypeData
    {
        return new VehicleServiceJobTypeData(
            tenantId: $this->tenantId(),
            code: strtolower(trim((string) $this->input('code', ''))),
            name: trim((string) $this->input('name')),
            organizationUnitId: $this->organizationUnitId(),
            description: $this->filled('description') ? trim((string) $this->input('description')) : null,
            tracksMileage: (bool) $this->boolean('tracks_mileage', false),
            isActive: (bool) $this->boolean('is_active', true),
            sortOrder: (int) $this->input('sort_order', 0),
            actorId: $this->currentUserId(),
            expectedVersion: (int) $this->input('expected_version'),
        );
    }
}
