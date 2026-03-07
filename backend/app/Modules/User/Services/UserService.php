<?php

namespace App\Modules\User\Services;

use App\Modules\User\DTOs\UserDTO;
use App\Modules\User\Events\UserCreated;
use App\Modules\User\Events\UserDeleted;
use App\Modules\User\Events\UserUpdated;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Event;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function listUsers(
        int $perPage = 15,
        array $filters = [],
        ?string $search = null,
        string $sortBy = 'created_at',
        string $sortDir = 'desc'
    ): LengthAwarePaginator {
        return $this->userRepository->paginate($perPage, $filters, $search, $sortBy, $sortDir);
    }

    public function getUser(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function createUser(array $data): User
    {
        $role = $data['role'] ?? 'staff';
        unset($data['password_confirmation']);

        $user = $this->userRepository->create($data);
        $user->assignRole($role);

        Event::dispatch(new UserCreated($user));

        return $user;
    }

    public function updateUser(int $id, array $data): User
    {
        unset($data['password_confirmation']);

        if (isset($data['role'])) {
            $user = $this->userRepository->find($id);
            $user->syncRoles([$data['role']]);
        }

        $user = $this->userRepository->update($id, $data);
        Event::dispatch(new UserUpdated($user));

        return $user;
    }

    public function deleteUser(int $id): bool
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return false;
        }

        $tenantId = $user->tenant_id;
        $result = $this->userRepository->delete($id);

        if ($result) {
            Event::dispatch(new UserDeleted($id, $tenantId));
        }

        return $result;
    }
}
