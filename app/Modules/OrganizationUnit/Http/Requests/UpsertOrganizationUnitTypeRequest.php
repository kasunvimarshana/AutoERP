<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertOrganizationUnitTypeRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        $creating = $this->isMethod('post');
        return [
            'expected_version' => $creating ? ['prohibited'] : ['required', 'integer', 'min:1'],
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'level' => [$creating ? 'required' : 'sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
