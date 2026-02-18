<?php

namespace Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:leave_types,code'],
            'description' => ['nullable', 'string'],
            'max_days_per_year' => ['required', 'integer', 'min:0'],
            'is_paid' => ['required', 'boolean'],
            'requires_approval' => ['required', 'boolean'],
            'is_carry_forward' => ['required', 'boolean'],
            'max_carry_forward_days' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
