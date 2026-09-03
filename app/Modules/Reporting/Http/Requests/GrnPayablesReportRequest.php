<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class GrnPayablesReportRequest extends TenantScopedRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'search' => ['nullable', 'string', 'max:150'],
            'sort' => ['nullable', 'string', 'max:80'],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'invoice_progress' => ['nullable', Rule::in(['not_invoiced', 'partially_invoiced', 'invoiced'])],
            'exposure_status' => ['nullable', Rule::in(['open', 'settled', 'credit'])],
        ];
    }
}
