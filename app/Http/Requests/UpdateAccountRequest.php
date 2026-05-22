<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
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
            'tenant_id' => 'sometimes|nullable|integer|exists:tenants,id',
            'organization_unit_id' => 'sometimes|nullable|integer|exists:organization_units,id',

            'parent_id' => 'nullable|integer|exists:accounts,id',
            'code' => 'required|string',
            'name' => 'required|string',
            'type' => 'required|string',
            'normal_balance' => 'required|numeric',
            'is_control_account' => 'required|boolean',
            'is_bank_account' => 'required|boolean',
            'is_cash_account' => 'required|boolean',
            'is_system' => 'required|boolean',
            'currency_id' => 'required|integer|exists:currencies,id',
            'description' => 'required|string',
            'is_active' => 'required|boolean',
            'allows_manual_posting' => 'required|boolean',
            'path' => 'required|string',
            'depth' => 'required|integer',
        ];
    }
}
