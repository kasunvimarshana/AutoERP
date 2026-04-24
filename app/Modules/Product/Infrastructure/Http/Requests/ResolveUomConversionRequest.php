<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveUomConversionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|min:1',
            'product_id' => 'nullable|integer|min:1',
            'from_uom_id' => 'required|integer|min:1',
            'to_uom_id' => 'required|integer|min:1',
            'quantity' => 'required|numeric|gt:0',
            'scale' => 'nullable|integer|min:2|max:16',
        ];
    }
}
