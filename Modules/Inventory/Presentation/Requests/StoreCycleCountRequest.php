<?php

namespace Modules\Inventory\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCycleCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'uuid'],
            'location_id'  => ['nullable', 'uuid'],
            'count_date'   => ['nullable', 'date'],
            'notes'        => ['nullable', 'string', 'max:2000'],
        ];
    }
}
