<?php
namespace Modules\User\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\User\Application\Contracts\ChangePasswordServiceInterface;
use Modules\User\Application\DTOs\ChangePasswordData;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Events\UserPasswordChanged;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class ChangePasswordService implements ChangePasswordServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $repository) {}

    public function execute(User $user, ChangePasswordData $data): bool
    {
        if (!$this->repository->verifyPassword($user, $data->currentPassword)) {
            throw new \InvalidArgumentException('Current password is incorrect.');
        }
        $result = $this->repository->changePassword($user, $data->newPassword);
        if ($result) {
            Event::dispatch(new UserPasswordChanged($user->tenantId, $user->id));
        }
        return $result;
    }
}
