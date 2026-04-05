<?php
declare(strict_types=1);
namespace Modules\User\Application\Contracts;

use Modules\User\Application\DTOs\UpdateProfileData;
use Modules\User\Domain\Entities\User;

interface UpdateProfileServiceInterface
{
    public function execute(int $id, UpdateProfileData $data): User;
}
