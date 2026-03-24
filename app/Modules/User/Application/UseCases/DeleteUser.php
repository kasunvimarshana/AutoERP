<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class DeleteUser
{
    public function __construct(
        private UserRepositoryInterface $userRepo
    ) {}

    public function execute(int $id): bool
    {
        $user = $this->userRepo->find($id);
        if (! $user) {
            throw new UserNotFoundException($id);
        }

        return $this->userRepo->delete($id);
    }
}
