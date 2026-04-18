<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListUnitOfMeasureRequest extends FormRequest
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
            'symbol' => 'nullable|string|max:10',
            'type' => 'nullable|string|in:unit,mass,volume,length,time,other',
            'is_base' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|max:50',
        ];
    }
}
