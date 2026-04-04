<?php
namespace Modules\User\Application\Contracts;

use Modules\User\Application\DTOs\UpdateProfileData;
use Modules\User\Domain\Entities\User;

interface UpdateProfileServiceInterface
{
    public function execute(User $user, UpdateProfileData $data): User;
}
