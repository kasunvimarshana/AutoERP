<?php

namespace Modules\IAM\DTOs;

use Modules\Core\DTOs\BaseDTO;

class ForgotPasswordDTO extends BaseDTO
{
    public string $email;
}
