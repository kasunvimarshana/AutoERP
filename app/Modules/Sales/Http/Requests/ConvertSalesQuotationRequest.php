<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

final class ConvertSalesQuotationRequest extends SalesRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'sales_order_date' => ['nullable', 'date'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
