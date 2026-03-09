<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->attributes->get('tenant_id') ?? $this->header('X-Tenant-ID');

        return [
            'name'     => ['required', 'string', 'max:255'],
            'code'     => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9_\-]+$/i',
                Rule::unique('warehouses')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'type'     => [
                'required',
                Rule::in(array_keys(config('inventory.warehouse_types', [
                    'standard', 'distribution', 'cold_storage', 'virtual', 'bonded',
                ]))),
            ],
            'address'  => ['nullable', 'array'],
            'address.street'   => ['nullable', 'string', 'max:255'],
            'address.city'     => ['nullable', 'string', 'max:100'],
            'address.state'    => ['nullable', 'string', 'max:100'],
            'address.country'  => ['nullable', 'string', 'size:2'],
            'address.postcode' => ['nullable', 'string', 'max:20'],
            'contact'  => ['nullable', 'array'],
            'contact.name'     => ['nullable', 'string', 'max:255'],
            'contact.email'    => ['nullable', 'email'],
            'contact.phone'    => ['nullable', 'string', 'max:30'],
            'capacity' => ['nullable', 'numeric', 'min:0'],
            'is_active'=> ['boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'A warehouse with this code already exists for your tenant.',
            'code.regex'  => 'Warehouse code may only contain letters, numbers, hyphens, and underscores.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->input('is_active', true),
        ]);

        if ($this->has('code')) {
            $this->merge(['code' => strtoupper($this->input('code'))]);
        }
    }
}
