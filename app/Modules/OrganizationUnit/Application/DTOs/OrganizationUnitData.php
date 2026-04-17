<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class OrganizationUnitData extends BaseDto
{
    public int $tenant_id;

    public ?int $type_id;

    public ?int $parent_id;

    public ?int $manager_user_id;

    public string $name;

    public ?string $code;

    /** @var array<string, mixed>|null */
    public ?array $metadata;

    public ?bool $is_active;

    public ?string $description;

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'type_id' => 'nullable|integer|exists:org_unit_types,id',
            'parent_id' => 'nullable|integer|exists:org_units,id',
            'manager_user_id' => 'nullable|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100',
            'metadata' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ];
    }
}
