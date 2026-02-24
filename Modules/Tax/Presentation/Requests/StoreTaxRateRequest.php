<?php

namespace Modules\Tax\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type'        => ['required', 'string', 'in:percentage,fixed'],
            'rate'        => ['required', 'numeric', 'min:0'],
            'region'      => ['nullable', 'string', 'max:255'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
