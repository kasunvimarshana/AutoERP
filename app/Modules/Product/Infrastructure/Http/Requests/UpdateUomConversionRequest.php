<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUomConversionRequest extends FormRequest
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
            'from_uom_id' => 'required|integer|min:1|different:to_uom_id',
            'to_uom_id' => 'required|integer|min:1|different:from_uom_id',
            'factor' => 'required|numeric|gt:0',
            'is_bidirectional' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
