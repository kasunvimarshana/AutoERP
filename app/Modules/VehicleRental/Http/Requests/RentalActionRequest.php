<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class RentalActionRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'reason' => ['nullable', 'string'],
            'replace' => ['nullable', 'boolean'],
            'mileage_override' => ['nullable', 'boolean'],
        ];
    }
}
