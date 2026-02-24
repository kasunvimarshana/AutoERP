<?php

namespace Modules\Reporting\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
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
            'type'        => ['required', 'string', 'in:sales,purchase,inventory,accounting,hr,pos,crm,project,custom'],
            'data_source' => ['nullable', 'string', 'max:255'],
            'fields'      => ['nullable', 'array'],
            'filters'     => ['nullable', 'array'],
            'group_by'    => ['nullable', 'array'],
            'sort_by'     => ['nullable', 'array'],
            'is_shared'   => ['boolean'],
        ];
    }
}
