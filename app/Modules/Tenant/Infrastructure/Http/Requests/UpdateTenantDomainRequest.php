<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->route('tenant');
        $domainId = (int) $this->route('domain');

        return [
            'domain' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('tenant_domains', 'domain')
                    ->ignore($domainId)
                    ->where(static fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'is_primary' => 'sometimes|required|boolean',
            'is_verified' => 'sometimes|required|boolean',
            'verified_at' => 'sometimes|nullable|date',
        ];
    }
}
