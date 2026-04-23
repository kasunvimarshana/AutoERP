<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListSerialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'nullable|integer|exists:tenants,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'variant_id' => 'nullable|integer',
            'batch_id' => 'nullable|integer|exists:batches,id',
            'status' => 'nullable|string|max:50',
            'serial_number' => 'nullable|string|max:100',
            'sort' => 'nullable|string|max:60',
            'per_page' => 'nullable|integer|min:1|max:200',
            'page' => 'nullable|integer|min:1',
        ];
    }
}
