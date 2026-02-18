<?php

namespace Modules\IAM\DTOs;

use Modules\Core\DTOs\BaseDTO;

class RegisterDTO extends BaseDTO
{
    public string $name;

    public string $email;

    public string $password;

    public string $password_confirmation;

    public ?int $tenant_id = null;

    public ?string $phone = null;

    public ?string $timezone = null;

    public ?string $locale = null;
}
