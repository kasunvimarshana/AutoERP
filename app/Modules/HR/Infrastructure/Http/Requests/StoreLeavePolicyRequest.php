<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeavePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer',
            'leave_type_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'accrual_type' => 'nullable|string|max:20',
            'accrual_amount' => 'nullable|numeric|min:0',
            'org_unit_id' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }
}
