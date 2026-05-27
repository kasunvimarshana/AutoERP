<?php

namespace Modules\Document\Presentation\API\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'max:100'],
            'action_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
