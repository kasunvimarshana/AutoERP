<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class OrganizationUnitUserData extends BaseDto
{
    public int $tenant_id;

    public int $org_unit_id;

    public int $user_id;

    public ?string $role = null;

    public bool $is_primary = false;

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'org_unit_id' => 'required|integer|exists:org_units,id',
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'nullable|string|max:255',
            'is_primary' => 'required|boolean',
        ];
    }
}
