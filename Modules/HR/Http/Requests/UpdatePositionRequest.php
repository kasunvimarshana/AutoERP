<?php

namespace Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $positionId = $this->route('position');

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', "unique:positions,code,{$positionId}"],
            'description' => ['nullable', 'string'],
            'grade' => ['nullable', 'string', 'max:50'],
            'min_salary' => ['nullable', 'numeric', 'min:0'],
            'max_salary' => ['nullable', 'numeric', 'min:0', 'gte:min_salary'],
            'responsibilities' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', 'in:active,inactive'],
        ];
    }
}
