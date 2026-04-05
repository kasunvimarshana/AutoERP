<?php
declare(strict_types=1);
namespace Modules\User\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class CreateUserData extends BaseDto
{
    public int $tenant_id;
    public string $name;
    public string $email;
    public string $password;
    public string $status = 'active';
    public ?string $phone = null;
}
