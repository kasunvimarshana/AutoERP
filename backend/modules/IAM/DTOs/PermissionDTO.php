<?php

namespace Modules\IAM\DTOs;

use Modules\Core\DTOs\BaseDTO;

class PermissionDTO extends BaseDTO
{
    public ?int $id = null;

    public string $name;

    public ?string $description = null;

    public string $resource;

    public string $action;

    public ?int $tenant_id = null;

    public bool $is_system = false;
}
