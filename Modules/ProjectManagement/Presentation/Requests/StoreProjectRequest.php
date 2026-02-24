<?php

namespace Modules\ProjectManagement\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
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
            'customer_id' => ['nullable', 'uuid'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date'],
            'budget'      => ['nullable', 'numeric', 'min:0'],
            'status'      => ['nullable', 'string', 'in:planning,active,on_hold,completed,cancelled'],
        ];
    }
}
