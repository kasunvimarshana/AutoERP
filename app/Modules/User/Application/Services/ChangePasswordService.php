<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Illuminate\Support\Facades\Hash;
use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\User\Application\Contracts\ChangePasswordServiceInterface;
use Modules\User\Domain\Events\UserPasswordChanged;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class ChangePasswordService extends BaseService implements ChangePasswordServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
        parent::__construct($userRepository);
    }

    protected function handle(array $data): mixed
    {
        $userId = (int) $data['user_id'];
        $currentPassword = (string) $data['current_password'];
        $newPassword = (string) $data['password'];

        $user = $this->userRepository->find($userId);
        if (! $user) {
            throw new UserNotFoundException($userId);
        }

        if (! $this->userRepository->verifyPassword($userId, $currentPassword)) {
            throw new DomainException('Current password is incorrect.');
        }

        $this->userRepository->changePassword($userId, Hash::make($newPassword));
        $this->addEvent(new UserPasswordChanged($user));

        return null;
    }
}
