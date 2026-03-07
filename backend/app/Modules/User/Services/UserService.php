<?php
namespace App\Modules\User\Services;

use App\Helpers\PaginationHelper;
use App\Interfaces\MessageBrokerInterface;
use App\Modules\User\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private MessageBrokerInterface $messageBroker,
    ) {}

    public function listUsers(array $filters, int $tenantId): mixed
    {
        $filters['tenant_id'] = $tenantId;
        ['per_page' => $perPage, 'page' => $page] = PaginationHelper::fromRequest(request());

        $query = $this->userRepository->all($filters, ['roles', 'tenant']);
        return PaginationHelper::paginate($query, $perPage, $page);
    }

    public function getUser(int $id): mixed
    {
        return $this->userRepository->find($id, ['roles', 'permissions', 'tenant']);
    }

    public function createUser(array $data, int $tenantId): mixed
    {
        return DB::transaction(function () use ($data, $tenantId) {
            $data['tenant_id'] = $tenantId;
            $user = $this->userRepository->create($data);

            if (isset($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            $this->messageBroker->publish('user.created', [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'email' => $user->email,
            ]);

            return $user->load(['roles', 'permissions', 'tenant']);
        });
    }

    public function updateUser(int $id, array $data): mixed
    {
        return DB::transaction(function () use ($id, $data) {
            $user = $this->userRepository->update($id, $data);

            if (isset($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            $this->messageBroker->publish('user.updated', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
            ]);

            return $user->load(['roles', 'permissions', 'tenant']);
        });
    }

    public function deleteUser(int $id): bool
    {
        $user = $this->userRepository->find($id);
        $result = $this->userRepository->delete($id);

        if ($result) {
            $this->messageBroker->publish('user.deleted', [
                'user_id' => $id,
                'tenant_id' => $user->tenant_id,
            ]);
        }

        return $result;
    }
}
