<?php

namespace Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:positions,code'],
            'description' => ['nullable', 'string'],
            'grade' => ['nullable', 'string', 'max:50'],
            'min_salary' => ['nullable', 'numeric', 'min:0'],
            'max_salary' => ['nullable', 'numeric', 'min:0', 'gte:min_salary'],
            'responsibilities' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
