<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ListVehicleServiceCommissionPolicyRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
