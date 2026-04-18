<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\User\Application\Contracts\ChangePasswordServiceInterface;
use Modules\User\Application\Services\Concerns\HandlesUserPasswordMutation;
use Modules\User\Domain\Events\UserPasswordChanged;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class ChangePasswordService extends BaseService implements ChangePasswordServiceInterface
{
    use HandlesUserPasswordMutation;

    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
        parent::__construct($userRepository);
    }

    protected function userRepository(): UserRepositoryInterface
    {
        return $this->userRepository;
    }

    protected function handle(array $data): mixed
    {
        $userId = (int) $data['user_id'];
        $currentPassword = (string) $data['current_password'];
        $newPassword = (string) $data['password'];

        $user = $this->findUserOrFail($userId);

        if (! $this->userRepository->verifyPassword($userId, $currentPassword)) {
            throw new DomainException('Current password is incorrect.');
        }

        $this->persistPassword($userId, $newPassword);
        $this->addEvent(new UserPasswordChanged($user));

        return null;
    }
}
