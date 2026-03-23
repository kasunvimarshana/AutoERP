<?php

namespace Modules\OrganizationUnit\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class OrganizationUnitData extends BaseDto
{
    public int $tenant_id;
    public string $name;
    public ?string $code;
    public ?string $description;
    public ?array $metadata;
    public ?int $parent_id;

    public function rules(): array
    {
        return [
            'tenant_id'   => 'required|integer|exists:tenants,id',
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'metadata'    => 'nullable|array',
            'parent_id'   => 'nullable|integer|exists:organization_units,id',
        ];
    }
}
