<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class VehicleServiceSalesSummaryReportRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'item_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
