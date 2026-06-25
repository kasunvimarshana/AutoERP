<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class SwitchOrganizationUnitRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return ['target_organization_unit_id' => ['required', 'integer', 'min:1']];
    }
}
