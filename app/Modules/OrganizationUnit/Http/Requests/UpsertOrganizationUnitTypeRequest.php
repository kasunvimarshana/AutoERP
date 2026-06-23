<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertOrganizationUnitTypeRequest extends TenantScopedRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => $this->isMethod('post') ? ['required', 'integer', 'min:1'] : ['sometimes', 'integer', 'min:1'],
            'name' => array_merge($required, ['string', 'max:255']),
            'level' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
