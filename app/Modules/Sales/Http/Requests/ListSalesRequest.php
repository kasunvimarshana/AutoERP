<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

final class ListSalesRequest extends SalesRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', 'max:100'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);
    }
}
