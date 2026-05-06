<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'row_version' => 'required|integer|min:1',
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:20',
            'description' => 'nullable|string',
            'max_days_per_year' => 'nullable|numeric|min:0',
            'carry_forward_days' => 'nullable|numeric|min:0',
            'is_paid' => 'nullable|boolean',
            'requires_approval' => 'nullable|boolean',
            'applicable_gender' => 'nullable|string|in:male,female,all',
            'min_service_days' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }
}
