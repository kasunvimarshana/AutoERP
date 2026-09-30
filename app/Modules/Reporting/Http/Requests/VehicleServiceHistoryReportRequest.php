<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;

final class VehicleServiceHistoryReportRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'job_status' => ['nullable', Rule::enum(VehicleServiceJobStatus::class)],
            'include_cancelled' => ['nullable', 'boolean'],
        ];
    }
}
