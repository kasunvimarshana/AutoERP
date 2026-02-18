<?php

namespace Modules\IAM\DTOs;

use Modules\Core\DTOs\BaseDTO;

class PasswordResetDTO extends BaseDTO
{
    public string $email;

    public string $token;

    public string $password;

    public string $password_confirmation;
}
