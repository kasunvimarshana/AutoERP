<?php
declare(strict_types=1);
namespace Modules\User\Application\Contracts;

use Modules\User\Application\DTOs\CreateUserData;
use Modules\User\Domain\Entities\User;

interface CreateUserServiceInterface
{
    public function execute(CreateUserData $data): User;
}
