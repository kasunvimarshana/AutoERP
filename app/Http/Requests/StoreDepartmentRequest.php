<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => 'nullable|integer|exists:tenants,id',
            'organization_unit_id' => 'nullable|integer|exists:organization_units,id',

            'parent_id' => 'nullable|integer|exists:departments,id',
            'name' => 'required|string',
            'code' => 'required|string',
            'depth' => 'required|integer',
            'path' => 'required|string',
            'is_active' => 'required|boolean',
            'description' => 'required|boolean',
        ];
    }
}
