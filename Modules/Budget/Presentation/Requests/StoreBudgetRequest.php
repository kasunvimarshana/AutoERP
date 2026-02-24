<?php

namespace Modules\Budget\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                    => ['required', 'string', 'max:255'],
            'description'             => ['nullable', 'string'],
            'period'                  => ['required', 'string', 'in:monthly,quarterly,annually'],
            'start_date'              => ['required', 'date'],
            'end_date'                => ['required', 'date', 'after_or_equal:start_date'],
            'lines'                   => ['nullable', 'array'],
            'lines.*.category'        => ['required', 'string', 'max:255'],
            'lines.*.description'     => ['nullable', 'string'],
            'lines.*.planned_amount'  => ['required', 'numeric', 'min:0'],
        ];
    }
}
