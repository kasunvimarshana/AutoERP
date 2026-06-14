<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class RunningChartContextRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'agreement_id' => ['required', 'integer', 'min:1'],
            'agreement_vehicle_id' => ['required', 'integer', 'min:1'],
            'usage_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
        ];
    }
}
