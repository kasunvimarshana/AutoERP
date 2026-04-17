<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\DeleteUserServiceInterface;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class DeleteUserService extends BaseService implements DeleteUserServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
        parent::__construct($userRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) $data['id'];
        $user = $this->userRepository->find($id);
        if (! $user) {
            throw new UserNotFoundException($id);
        }

        return $this->userRepository->delete($id);
    }
}
