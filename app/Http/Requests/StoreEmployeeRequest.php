<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
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
            //
            'tenant_id' => 'nullable|integer|exists:tenants,id',
            'organization_unit_id' => 'nullable|integer|exists:organization_units,id',
            'code' => 'required|string',
            'registration_number' => 'required|string',
            'status' => 'required|string',
            'notes' => 'required|string',
            'department_id' => 'required|integer|exists:departments,id',
            'designation_id' => 'required|integer|exists:designations,id',
            'employment_type_id' => 'required|integer|exists:employment_types,id',
            'hire_date' => 'required|date',
            'confirmation_date' => 'required|date',
            'termination_date' => 'nullable|required|date',
            'termination_reason' => 'required|string',
            'personal_email' => 'required|email',
            'phone' => 'required|string',
            'mobile' => 'required|string',
            'address_line1' => 'required|string',
            'address_line2' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'postal_code' => 'required|string',
            'country_id' => 'required|integer|exists:countries,id',
            'tax_number' => 'required|string',
            'social_security_number' => 'required|string',
            'bank_name' => 'required|string',
            'bank_account_number' => 'required|string',
            'bank_routing_number' => 'required|string',

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
