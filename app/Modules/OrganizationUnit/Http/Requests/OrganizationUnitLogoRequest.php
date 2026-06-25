<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class OrganizationUnitLogoRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'logo' => ['required', 'file', 'max:'.max((int) config('organization-unit.storage.logo.max_size_kb', 5120), 1)],
        ];
    }
}
