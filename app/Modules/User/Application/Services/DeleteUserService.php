<?php

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Application\Contracts\DeleteUserServiceInterface;

class DeleteUserService extends BaseService implements DeleteUserServiceInterface
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->userRepository = $repository;
    }

    protected function handle(array $data): bool
    {
        $id = $data['id'];
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw new UserNotFoundException($id);
        }
        return $this->userRepository->delete($id);
    }
}
