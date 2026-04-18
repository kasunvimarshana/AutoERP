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
            'from_uom_id' => 'required|integer|exists:units_of_measure,id|different:to_uom_id',
            'to_uom_id' => 'required|integer|exists:units_of_measure,id|different:from_uom_id',
            'factor' => 'required|numeric|gt:0',
        ];
    }
}
