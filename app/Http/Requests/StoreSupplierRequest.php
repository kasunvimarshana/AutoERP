<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
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
            'code' => 'required|string',
            'registration_number' => 'required|string',
            'status' => 'required|string',
            'notes' => 'required|string',
            'type' => 'required|string',
            'tax_number' => 'required|string',
            'currency_id' => 'required|integer|exists:currencies,id',
            'credit_limit' => 'required|numeric',
            'payment_terms_days' => 'required|integer',
            'ap_account_id' => 'required|integer|exists:accounts,id',

            'user' => 'required|array',
            'user.first_name' => 'required|string',
            'user.last_name' => 'required|string',
            'user.email' => 'required|email',
            'user.password' => 'required|string|confirmed',
            'user.password_confirmation' => 'required|string',
            'user.avatar_file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'user.phone' => 'required|string',
            'user.preferences' => 'required|array',
            'user.date_of_birth' => 'required|date',
            'user.gender' => 'required|string',
            'user.marital_status' => 'required|string',
            'user.roles' => 'required|array',
            'user.roles.*' => 'integer|exists:roles,id'
        ];
    }
}
