<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class DepartmentData extends BaseDto
{
    public int $tenant_id;

    public string $name;

    public ?string $code = null;

    public ?string $description = null;

    public ?int $manager_id = null;

    public ?int $parent_id = null;

    public ?array $metadata = null;

    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'tenant_id'   => 'required|integer',
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'manager_id'  => 'nullable|integer',
            'parent_id'   => 'nullable|integer',
            'metadata'    => 'nullable|array',
            'is_active'   => 'boolean',
        ];
    }
}
