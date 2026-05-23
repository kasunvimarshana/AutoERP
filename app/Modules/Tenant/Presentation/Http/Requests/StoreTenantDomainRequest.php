<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantDomainRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('domain')) {
            $this->merge(['domain' => strtolower(trim((string) $this->input('domain')))]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:255', 'unique:tenant_domains,domain'],
            'is_primary' => ['nullable', 'boolean'],
            'is_verified' => ['nullable', 'boolean'],
            'verified_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
