<?php

namespace Modules\IAM\DTOs;

use Modules\Core\DTOs\BaseDTO;

class LoginDTO extends BaseDTO
{
    public string $email;

    public string $password;

    public bool $remember = false;

    public ?string $mfa_code = null;
}
