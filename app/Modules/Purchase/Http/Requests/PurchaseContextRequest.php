<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

final class PurchaseContextRequest extends PurchaseRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'item_variant_id' => ['nullable', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'uom_id' => ['nullable', 'integer', 'min:1'],
            'purchase_date' => ['nullable', 'date'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:150'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
    }
}
