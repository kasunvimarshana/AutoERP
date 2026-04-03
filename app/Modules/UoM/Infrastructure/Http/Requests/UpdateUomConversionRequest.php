<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Http\Requests;

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
            'from_uom_id' => 'sometimes|required|integer|exists:units_of_measure,id',
            'to_uom_id'   => 'sometimes|required|integer|exists:units_of_measure,id',
            'factor'      => 'sometimes|required|numeric|min:0',
            'is_active'   => 'boolean',
        ];
    }
}
