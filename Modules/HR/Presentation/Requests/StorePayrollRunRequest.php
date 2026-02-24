<?php

namespace Modules\HR\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_start' => ['required', 'date'],
            'period_end'   => ['required', 'date'],
        ];
    }
}
