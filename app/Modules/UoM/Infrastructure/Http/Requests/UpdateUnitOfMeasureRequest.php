<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitOfMeasureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uom_category_id' => 'sometimes|required|integer|exists:uom_categories,id',
            'name'            => 'sometimes|required|string|max:255',
            'code'            => 'sometimes|required|string|max:50',
            'symbol'          => 'sometimes|required|string|max:20',
            'is_base_unit'    => 'boolean',
            'factor'          => 'numeric|min:0',
            'description'     => 'nullable|string',
            'is_active'       => 'boolean',
        ];
    }
}
