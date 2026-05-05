<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateServicePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_task_id' => ['sometimes', 'integer', 'min:1'],
            'product_id' => ['sometimes', 'integer', 'min:1'],
            'part_source' => ['sometimes', 'string', 'in:inventory,non_inventory,special_order'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'uom_id' => ['sometimes', 'integer', 'min:1'],
            'unit_cost' => ['sometimes', 'numeric', 'min:0'],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
            'is_warranty_covered' => ['sometimes', 'boolean'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
