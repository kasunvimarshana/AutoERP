<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\Http\Requests\Concerns\HasExpectedVehicleServiceJobVersion;

final class IssueVehicleServiceInventoryRequest extends TenantScopedRequest
{
    use HasExpectedVehicleServiceJobVersion;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => $this->expectedVersionRules(),
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'line_ids' => ['nullable', 'array'],
            'line_ids.*' => ['integer', 'min:1'],
        ];
    }
}
