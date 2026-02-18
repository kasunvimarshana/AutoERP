<?php

namespace Modules\IAM\DTOs;

use Modules\Core\DTOs\BaseDTO;

class RoleDTO extends BaseDTO
{
    public ?int $id = null;

    public string $name;

    public ?string $description = null;

    public ?int $tenant_id = null;

    public ?int $parent_id = null;

    public array $permissions = [];

    public bool $is_system = false;
}
