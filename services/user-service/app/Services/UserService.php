<?php

namespace App\Services;

use App\DTOs\UserDTO;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService extends BaseService
{
    public function __construct(protected UserRepositoryInterface $repository) {}

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    public function getAllUsers(Request $request, ?int $tenantId = null): Collection|LengthAwarePaginator
    {
        $query = User::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        // Search
        if ($term = $request->input('search')) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('email', 'LIKE', "%{$term}%");
            });
        }

        // Filter by role
        if ($role = $request->input('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Sort
        $sortColumn    = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_dir', 'desc');
        $allowedSorts  = ['name', 'email', 'created_at', 'updated_at'];

        if (in_array($sortColumn, $allowedSorts, true)) {
            $query->orderBy($sortColumn, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        return $this->repository->paginateConditional($query, $request);
    }

    public function getUserById(int|string $id): ?Model
    {
        return $this->repository->withRelations(['roles', 'permissions', 'tenant'])->find($id);
    }

    public function createUser(UserDTO $dto): Model
    {
        $data             = $dto->toArray();
        $data['password'] = Hash::make($data['password']);

        $user = $this->repository->create($data);

        // Assign default role if provided
        if (! empty($dto->roleIds)) {
            $this->repository->syncRoles($user->id, $dto->roleIds);
        }

        event(new UserCreated($user));
        Log::info('User created', ['user_id' => $user->id, 'tenant_id' => $user->tenant_id]);

        return $user->load(['roles', 'permissions', 'tenant']);
    }

    public function updateUser(int|string $id, array $data): Model
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (isset($data['role_ids'])) {
            $this->repository->syncRoles($id, $data['role_ids']);
            unset($data['role_ids']);
        }

        if (isset($data['permission_ids'])) {
            $this->repository->syncPermissions($id, $data['permission_ids']);
            unset($data['permission_ids']);
        }

        $user = $this->repository->update($id, $data);

        event(new UserUpdated($user));
        Log::info('User updated', ['user_id' => $user->id]);

        return $user->load(['roles', 'permissions', 'tenant']);
    }

    public function deleteUser(int|string $id): bool
    {
        $user   = $this->repository->find($id);
        $result = $this->repository->delete($id);

        if ($result && $user) {
            event(new UserDeleted($user));
            Log::info('User deleted', ['user_id' => $id]);
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Role / Permission management
    // -------------------------------------------------------------------------

    public function assignRolesToUser(int|string $userId, array $roleIds): Model
    {
        $this->repository->assignRoles($userId, $roleIds);

        return $this->getUserById($userId);
    }

    public function syncUserRoles(int|string $userId, array $roleIds): Model
    {
        $this->repository->syncRoles($userId, $roleIds);

        return $this->getUserById($userId);
    }

    public function assignPermissionsToUser(int|string $userId, array $permissionIds): Model
    {
        $this->repository->assignPermissions($userId, $permissionIds);

        return $this->getUserById($userId);
    }

    // -------------------------------------------------------------------------
    // Tenant-scoped helpers
    // -------------------------------------------------------------------------

    public function getTenantUsers(int|string $tenantId, Request $request): Collection|LengthAwarePaginator
    {
        $query = User::query()->where('tenant_id', $tenantId);

        return $this->repository->paginateConditional($query, $request);
    }

    public function getActiveUsers(int|string $tenantId): Collection
    {
        return $this->repository->getActiveUsers($tenantId);
    }

    // -------------------------------------------------------------------------
    // Profile
    // -------------------------------------------------------------------------

    public function updateProfile(int|string $userId, array $data): Model
    {
        // Strip sensitive fields that profile updates should not touch
        unset($data['tenant_id'], $data['is_active'], $data['role_ids']);

        return $this->updateUser($userId, $data);
    }

    public function changePassword(int|string $userId, string $newPassword): bool
    {
        $user = $this->repository->find($userId);

        if (! $user) {
            return false;
        }

        $this->repository->update($userId, ['password' => Hash::make($newPassword)]);

        return true;
    }

    // -------------------------------------------------------------------------
    // Cross-service data fetch (example: fetch orders for a user)
    // -------------------------------------------------------------------------

    public function fetchUserOrders(int|string $userId): mixed
    {
        $orderServiceUrl = config('services.order_service.url', 'http://order-service');

        return $this->repository->crossServiceFetch($orderServiceUrl, "/api/orders?user_id={$userId}");
    }
}
