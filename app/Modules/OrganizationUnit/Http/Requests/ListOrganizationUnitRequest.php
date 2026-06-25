<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ListOrganizationUnitRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'include_retired' => ['sometimes', 'boolean'],
            'parent_candidates_for' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
