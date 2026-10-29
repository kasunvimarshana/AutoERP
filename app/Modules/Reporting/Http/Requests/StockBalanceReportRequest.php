<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class StockBalanceReportRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'search' => ['nullable', 'string', 'max:120'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'location_id' => ['nullable', 'integer', 'min:1'],
            'include_zero' => ['nullable', 'boolean'],
            'item_id' => ['nullable', 'integer', 'min:1'],
            'base_uom_id' => ['nullable', 'integer', 'min:1'],
            'item_variant_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
