<?php

declare(strict_types=1);

namespace Modules\Employee\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->input('tenant_id');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'user_id' => [
                'nullable',
                'integer',
                'required_without:user',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
                Rule::unique('employees', 'user_id'),
            ],
            'user' => 'required_without:user_id|array',
            'user.email' => [
                'required_with:user',
                'email',
                Rule::unique('users', 'email')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'user.first_name' => 'required_with:user|string|max:255',
            'user.last_name' => 'required_with:user|string|max:255',
            'user.phone' => 'nullable|string|max:30',
            'user.address' => 'nullable|array',
            'user.preferences' => 'nullable|array',
            'user.active' => 'nullable|boolean',
            'user.avatar' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,webp,svg',
            'employee_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('employees', 'employee_code')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)
                ),
            ],
            'org_unit_id' => [
                'nullable',
                'integer',
                Rule::exists('org_units', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'job_title' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'termination_date' => 'nullable|date',
            'metadata' => 'nullable|array',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $headerTenantId = (int) $this->header('X-Tenant-ID');
            $payloadTenantId = (int) $this->input('tenant_id');

            if ($headerTenantId > 0 && $payloadTenantId > 0 && $headerTenantId !== $payloadTenantId) {
                $validator->errors()->add('tenant_id', 'Tenant mismatch between X-Tenant-ID header and payload.');
            }

            $hireDate = $this->input('hire_date');
            $terminationDate = $this->input('termination_date');

            if ($hireDate !== null && $terminationDate !== null && strtotime((string) $terminationDate) < strtotime((string) $hireDate)) {
                $validator->errors()->add('termination_date', 'Termination date cannot be before hire date.');
            }
        });
    }
}
