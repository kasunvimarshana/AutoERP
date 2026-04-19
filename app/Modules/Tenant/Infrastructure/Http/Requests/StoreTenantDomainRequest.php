<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->route('tenant');

        return [
            'domain' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tenant_domains', 'domain')->where(
                    static fn ($query) => $query->where('tenant_id', $tenantId)
                ),
            ],
            'is_primary' => 'required|boolean',
            'is_verified' => 'required|boolean',
            'verified_at' => 'nullable|date',
        ];
    }
}
