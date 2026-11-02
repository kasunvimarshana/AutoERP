<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ReportRunRequest extends TenantScopedRequest
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
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'string', 'max:80'],
            'direction' => ['nullable', 'in:asc,desc'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'filters' => ['nullable', 'array'],
            'filters.*' => ['nullable'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $filters = $this->input('filters', []);

        return is_array($filters) ? $filters : [];
    }
}
