<?php

declare(strict_types=1);

namespace Modules\Customer\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertCustomerContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $aliases = [];

        if ($this->has('name') && ! $this->has('contact_name')) {
            $aliases['contact_name'] = $this->input('name');
        }

        if ($this->has('role') && ! $this->has('designation')) {
            $aliases['designation'] = $this->input('role');
        }

        if ($aliases !== []) {
            $this->merge($aliases);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'row_version' => ['nullable', 'integer', 'min:0'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'metadata' => ['nullable', 'array'],
            'customer_id' => array_merge($required, ['integer', 'min:1', 'exists:customers,id']),
            'contact_name' => array_merge($required, ['string', 'max:180']),
            'designation' => ['nullable', 'string', 'max:180'],
            'department' => ['nullable', 'string', 'max:180'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'mobile' => ['nullable', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
