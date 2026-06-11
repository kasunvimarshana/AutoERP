<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ConvertSalesQuotationRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'sales_order_date' => ['nullable', 'date'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
