<?php
declare(strict_types=1);
namespace Modules\User\Application\Contracts;

use Modules\User\Application\DTOs\UpdateUserData;
use Modules\User\Domain\Entities\User;

interface UpdateUserServiceInterface
{
    public function execute(int $id, UpdateUserData $data): User;
}
