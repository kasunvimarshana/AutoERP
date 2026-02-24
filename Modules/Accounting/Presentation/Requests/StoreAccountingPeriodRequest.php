<?php

namespace Modules\Accounting\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountingPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date'   => ['required', 'date_format:Y-m-d', 'after:start_date'],
        ];
    }
}
