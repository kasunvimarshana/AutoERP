<?php

namespace Modules\HR\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                       => ['required', 'string', 'max:255'],
            'code'                       => ['required', 'string', 'max:50'],
            'description'                => ['nullable', 'string', 'max:1000'],
            'lines'                      => ['required', 'array', 'min:1'],
            'lines.*.component_id'       => ['required', 'string'],
            'lines.*.sequence'           => ['nullable', 'integer', 'min:1'],
            'lines.*.override_amount'    => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
