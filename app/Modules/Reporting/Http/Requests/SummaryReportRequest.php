<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class SummaryReportRequest extends TenantScopedRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }
}
