<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetUomConversionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'conversions' => ['required', 'array', 'min:1'],
            'conversions.*.from_uom' => ['required', 'string', 'max:50'],
            'conversions.*.to_uom' => ['required', 'string', 'max:50'],
            'conversions.*.factor' => ['required', 'numeric', 'min:0.0001'],
        ];
    }
}
