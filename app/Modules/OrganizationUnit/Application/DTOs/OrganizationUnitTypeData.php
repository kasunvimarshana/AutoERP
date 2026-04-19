<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class OrganizationUnitTypeData extends BaseDto
{
    public int $tenant_id;

    public string $name;

    public int $level = 0;

    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'name' => 'required|string|max:255',
            'level' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ];
    }
}
