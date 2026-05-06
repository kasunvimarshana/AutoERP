<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->input('tenant_id');
        $supplierId = (int) $this->route('supplier');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'user_id' => 'prohibited',
            'user' => 'sometimes|array',
            'user.email' => 'sometimes|email',
            'user.first_name' => 'sometimes|string|max:255',
            'user.last_name' => 'sometimes|string|max:255',
            'user.phone' => 'sometimes|nullable|string|max:30',
            'user.address' => 'sometimes|nullable|array',
            'user.preferences' => 'sometimes|nullable|array',
            'user.active' => 'sometimes|boolean',
            'user.avatar' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,webp,svg',
            'supplier_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('suppliers', 'supplier_code')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($supplierId),
            ],
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:individual,company',
            'org_unit_id' => [
                'nullable',
                'integer',
                Rule::exists('org_units', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'tax_number' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'payment_terms_days' => 'nullable|integer|min:0|max:3650',
            'ap_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable|string',
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
        });
    }
}
