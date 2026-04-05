<?php
declare(strict_types=1);
namespace Modules\User\Application\Services;

use Illuminate\Support\Facades\Hash;
use Modules\User\Application\Contracts\CreateUserServiceInterface;
use Modules\User\Application\DTOs\CreateUserData;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Events\UserCreated;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class CreateUserService implements CreateUserServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $repo) {}

    public function execute(CreateUserData $data): User
    {
        $user = $this->repo->create([
            'tenant_id' => $data->tenant_id,
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
            'status' => $data->status ?? 'active',
            'phone' => $data->phone,
        ]);
        event(new UserCreated($user->getTenantId(), $user->getId(), $user->getEmail()));
        return $user;
    }
}
