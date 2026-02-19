<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExecuteReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('report'));
    }

    public function rules(): array
    {
        return [
            'filters' => ['nullable', 'array'],
            'filters.*.field' => ['required_with:filters', 'string'],
            'filters.*.operator' => ['required_with:filters', 'string'],
            'filters.*.value' => ['required_with:filters'],
        ];
    }
}
