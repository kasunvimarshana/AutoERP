<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

final class SalesContextRequest extends SalesRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'item_variant_id' => ['nullable', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'uom_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'sales_date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
    }
}
