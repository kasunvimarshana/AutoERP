<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ListOrganizationUnitTypeRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [];
    }
}
