<?php

namespace Modules\HR\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $leaveTypeId = $this->route('leave_type');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', "unique:leave_types,code,{$leaveTypeId}"],
            'description' => ['nullable', 'string'],
            'max_days_per_year' => ['sometimes', 'required', 'integer', 'min:0'],
            'is_paid' => ['sometimes', 'required', 'boolean'],
            'requires_approval' => ['sometimes', 'required', 'boolean'],
            'is_carry_forward' => ['sometimes', 'required', 'boolean'],
            'max_carry_forward_days' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'required', 'in:active,inactive'],
        ];
    }
}
