<?php
declare(strict_types=1);
namespace Modules\User\Application\Services;

use Illuminate\Support\Facades\Hash;
use Modules\User\Application\Contracts\ChangePasswordServiceInterface;
use Modules\User\Application\DTOs\ChangePasswordData;
use Modules\User\Domain\Events\UserPasswordChanged;
use Modules\User\Domain\Exceptions\InvalidCredentialsException;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class ChangePasswordService implements ChangePasswordServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $repo) {}

    public function execute(int $id, ChangePasswordData $data): bool
    {
        $user = $this->repo->findById($id);
        if (!$user) {
            throw new UserNotFoundException($id);
        }
        if (!$this->repo->verifyPassword($id, $data->current_password)) {
            throw new InvalidCredentialsException();
        }
        $result = $this->repo->changePassword($id, Hash::make($data->new_password));
        event(new UserPasswordChanged($user->getTenantId(), $user->getId()));
        return $result;
    }
}
