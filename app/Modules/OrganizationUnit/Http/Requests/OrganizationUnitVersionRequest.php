<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class OrganizationUnitVersionRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return ['expected_version' => ['required', 'integer', 'min:1']];
    }
}
