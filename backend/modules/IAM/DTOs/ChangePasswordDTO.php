<?php

namespace Modules\IAM\DTOs;

use Modules\Core\DTOs\BaseDTO;

class ChangePasswordDTO extends BaseDTO
{
    public string $current_password;

    public string $new_password;

    public string $new_password_confirmation;
}
