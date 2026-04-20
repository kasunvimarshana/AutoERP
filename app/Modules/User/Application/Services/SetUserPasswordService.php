<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\SetUserPasswordServiceInterface;
use Modules\User\Application\Services\Concerns\HandlesUserPasswordMutation;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class SetUserPasswordService extends BaseService implements SetUserPasswordServiceInterface
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
        $password = (string) $data['password'];

        $this->findUserOrFail($userId);
        $this->persistPassword($userId, $password);

        return null;
    }
}
