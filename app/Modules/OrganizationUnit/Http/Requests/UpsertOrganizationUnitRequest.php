<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertOrganizationUnitRequest extends TenantScopedRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'tenant_id' => ['sometimes', 'integer', 'min:1'],
            'expected_version' => $creating ? ['prohibited'] : ['required', 'integer', 'min:1'],
            'type_id' => ['nullable', 'integer', 'min:1'],
            'parent_id' => $creating ? ['required', 'integer', 'min:1'] : ['sometimes', 'nullable', 'integer', 'min:1'],
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'image_path' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['sometimes', 'nullable', 'string'],
            'metadata' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
