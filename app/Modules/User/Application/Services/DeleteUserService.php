<?php
declare(strict_types=1);
namespace Modules\User\Application\Services;

use Modules\User\Application\Contracts\DeleteUserServiceInterface;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class DeleteUserService implements DeleteUserServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $repo) {}

    public function execute(int $id): bool
    {
        $user = $this->repo->findById($id);
        if (!$user) {
            throw new UserNotFoundException($id);
        }
        return $this->repo->delete($id);
    }
}
