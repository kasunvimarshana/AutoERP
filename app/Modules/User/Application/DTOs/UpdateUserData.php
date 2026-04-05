<?php
declare(strict_types=1);
namespace Modules\User\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateUserData extends BaseDto
{
    public ?string $name = null;
    public ?string $status = null;
    public ?string $phone = null;
}
