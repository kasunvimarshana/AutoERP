<?php

namespace Modules\IAM\DTOs;

use Modules\Core\DTOs\BaseDTO;

class UserDTO extends BaseDTO
{
    public ?int $id = null;

    public string $name;

    public string $email;

    public ?string $avatar = null;

    public ?string $phone = null;

    public ?string $timezone = null;

    public ?string $locale = null;

    public bool $is_active = true;

    public ?int $tenant_id = null;

    public ?array $roles = null;

    public ?array $permissions = null;
}
