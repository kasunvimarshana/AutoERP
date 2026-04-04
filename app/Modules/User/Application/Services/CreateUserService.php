<?php
namespace Modules\User\Application\Services;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Modules\User\Application\Contracts\CreateUserServiceInterface;
use Modules\User\Application\DTOs\UserData;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Events\UserCreated;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class CreateUserService implements CreateUserServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $repository) {}

    public function execute(UserData $data): User
    {
        $user = $this->repository->create([
            'tenant_id' => $data->tenantId,
            'name'      => $data->name,
            'email'     => $data->email,
            'password'  => Hash::make($data->password),
            'status'    => $data->status,
        ]);
        Event::dispatch(new UserCreated($user->tenantId, $user->id));
        return $user;
    }
}
