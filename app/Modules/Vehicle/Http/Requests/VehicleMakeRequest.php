<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\DTOs\VehicleMakeData;

abstract class VehicleMakeRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): VehicleMakeData
    {
        return new VehicleMakeData(
            tenantId: $this->tenantId(),
            code: (string) $this->input('code'),
            name: (string) $this->input('name'),
            organizationUnitId: $this->organizationUnitId(),
            description: $this->filled('description') ? (string) $this->input('description') : null,
            isActive: (bool) $this->input('is_active', true),
        );
    }
}
