<?php
namespace Modules\User\Application\Contracts;

use Modules\User\Application\DTOs\UserData;
use Modules\User\Domain\Entities\User;

interface CreateUserServiceInterface
{
    public function execute(UserData $data): User;
}
