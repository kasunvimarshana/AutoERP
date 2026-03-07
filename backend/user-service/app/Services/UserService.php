<?php

namespace App\Services;

use App\DTOs\UserDTO;
use App\Enums\UserRole;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function list(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        return $this->userRepository->paginate($filters, $perPage);
    }

    public function findById(int $id): User
    {
        return $this->userRepository->findById($id)
            ?? throw new \Illuminate\Database\Eloquent\ModelNotFoundException('User not found.');
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            if ($this->userRepository->findByEmail($data['email'])) {
                throw ValidationException::withMessages([
                    'email' => ['Email already exists in this tenant.'],
                ]);
            }

            $dto = UserDTO::fromArray($data);
            $user = $this->userRepository->create($dto->toArray());

            event(new UserCreated($user));

            return $user;
        });
    }

    public function update(int $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {
            $user = $this->findById($id);
            $updated = $this->userRepository->update($user, $data);

            event(new UserUpdated($updated));

            return $updated;
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $user = $this->findById($id);
            event(new UserDeleted($user));
            $this->userRepository->delete($user);
        });
    }

    public function activate(int $id): User
    {
        return $this->update($id, ['is_active' => true]);
    }

    public function deactivate(int $id): User
    {
        return $this->update($id, ['is_active' => false]);
    }

    public function updateRoles(int $id, array $roles): User
    {
        $validRoles = UserRole::values();
        foreach ($roles as $role) {
            if (! in_array($role, $validRoles)) {
                throw ValidationException::withMessages([
                    'roles' => ["Invalid role: {$role}. Must be one of: ".implode(', ', $validRoles)],
                ]);
            }
        }

        return $this->update($id, ['roles' => $roles]);
    }

    public function updateAttributes(int $id, array $attributes): User
    {
        $user = $this->findById($id);
        $merged = array_merge($user->attributes ?? [], $attributes);

        return $this->update($id, ['attributes' => $merged]);
    }
}
