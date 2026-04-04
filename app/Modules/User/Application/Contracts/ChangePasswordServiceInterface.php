<?php
namespace Modules\User\Application\Contracts;

use Modules\User\Application\DTOs\ChangePasswordData;
use Modules\User\Domain\Entities\User;

interface ChangePasswordServiceInterface
{
    public function execute(User $user, ChangePasswordData $data): bool;
}
