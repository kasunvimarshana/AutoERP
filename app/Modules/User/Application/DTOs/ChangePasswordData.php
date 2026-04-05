<?php
declare(strict_types=1);
namespace Modules\User\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class ChangePasswordData extends BaseDto
{
    public string $current_password;
    public string $new_password;
}
