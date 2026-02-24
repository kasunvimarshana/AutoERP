<?php

namespace Modules\Manufacturing\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'                      => ['required', 'uuid'],
            'product_name'                    => ['required', 'string', 'max:255'],
            'version'                         => ['nullable', 'string', 'max:50'],
            'quantity'                        => ['required', 'numeric', 'min:0.001'],
            'unit'                            => ['required', 'string', 'max:50'],
            'status'                          => ['nullable', 'in:draft,active,obsolete'],
            'notes'                           => ['nullable', 'string'],
            'lines'                           => ['required', 'array', 'min:1'],
            'lines.*.component_product_id'    => ['required', 'uuid'],
            'lines.*.component_name'          => ['required', 'string', 'max:255'],
            'lines.*.quantity'                => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit'                    => ['nullable', 'string', 'max:50'],
            'lines.*.scrap_rate'              => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
