<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Supplier\DTOs\SupplierContactData;

abstract class SupplierContactRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'contact_name' => ['required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:150'],
            'department' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'is_primary' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function toData(): SupplierContactData
    {
        return new SupplierContactData(
            contactName: (string) $this->input('contact_name'),
            designation: $this->nullableString('designation'),
            department: $this->nullableString('department'),
            email: $this->nullableString('email'),
            phone: $this->nullableString('phone'),
            mobile: $this->nullableString('mobile'),
            isPrimary: $this->boolean('is_primary'),
            isActive: $this->boolean('is_active', true),
            notes: $this->nullableString('notes'),
        );
    }

    private function nullableString(string $key): ?string
    {
        return $this->filled($key) ? (string) $this->input($key) : null;
    }
}
