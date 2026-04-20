<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListPriceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'nullable|integer|min:1',
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|in:purchase,sales',
            'currency_id' => 'nullable|integer|min:1',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:50',
        ];
    }
}
