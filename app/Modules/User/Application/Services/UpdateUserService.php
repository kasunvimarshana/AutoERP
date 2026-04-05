<?php
declare(strict_types=1);
namespace Modules\User\Application\Services;

use Modules\User\Application\Contracts\UpdateUserServiceInterface;
use Modules\User\Application\DTOs\UpdateUserData;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Events\UserUpdated;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class UpdateUserService implements UpdateUserServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $repo) {}

    public function execute(int $id, UpdateUserData $data): User
    {
        $user = $this->repo->update($id, array_filter($data->toArray(), fn($v) => $v !== null));
        if (!$user) {
            throw new UserNotFoundException($id);
        }
        event(new UserUpdated($user->getTenantId(), $user->getId()));
        return $user;
    }
}
