<?php

namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\ValueObjects\Email;
use Modules\User\Domain\ValueObjects\PhoneNumber;
use Modules\User\Domain\ValueObjects\Address;
use Modules\User\Domain\ValueObjects\UserPreferences;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\RoleModel;

class EloquentUserRepository extends EloquentRepository implements UserRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new UserModel());
    }

    public function findByEmail(int $tenantId, string $email): ?User
    {
        $model = $this->model->where('tenant_id', $tenantId)
            ->where('email', $email)
            ->first();
        return $model ? $this->toDomainEntity($model) : null;
    }

    public function save(User $user): User
    {
        $data = [
            'tenant_id'   => $user->getTenantId(),
            'email'       => $user->getEmail()->value(),
            'first_name'  => $user->getFirstName(),
            'last_name'   => $user->getLastName(),
            'phone'       => $user->getPhone()?->value(),
            'address'     => $user->getAddress()?->toArray(),
            'preferences' => $user->getPreferences()->toArray(),
            'active'      => $user->isActive(),
        ];

        if ($user->getId()) {
            $model = $this->update($user->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        // Sync roles
        if ($user->getRoles()->isNotEmpty()) {
            $roleIds = $user->getRoles()->pluck('id')->toArray();
            $model->roles()->sync($roleIds);
        }

        $model->load('roles.permissions');
        return $this->toDomainEntity($model);
    }

    public function syncRoles(User $user, array $roleIds): void
    {
        $model = $this->model->find($user->getId());
        if ($model) {
            $model->roles()->sync($roleIds);
        }
    }

    public function get(array $filters = []): \Illuminate\Support\Collection
    {
        $query = $this->model->newQuery();
        if (isset($filters['name'])) {
            $query->where(function($q) use ($filters) {
                $q->where('first_name', 'like', '%' . $filters['name'] . '%')
                  ->orWhere('last_name', 'like', '%' . $filters['name'] . '%');
            });
        }
        if (isset($filters['email'])) {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }
        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }
        $models = $query->get();
        return $models->map(fn($m) => $this->toDomainEntity($m));
    }

    // Paginate, delete, etc. from EloquentRepository

    private function toDomainEntity(UserModel $model): User
    {
        $phone = $model->phone ? new PhoneNumber($model->phone) : null;
        $address = $model->address ? Address::fromArray($model->address) : null;
        $preferences = new UserPreferences(
            $model->preferences['language'] ?? 'en',
            $model->preferences['timezone'] ?? 'UTC',
            $model->preferences['notifications'] ?? []
        );

        $user = new User(
            tenantId: $model->tenant_id,
            email: new Email($model->email),
            firstName: $model->first_name,
            lastName: $model->last_name,
            phone: $phone,
            address: $address,
            preferences: $preferences,
            active: $model->active,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );

        foreach ($model->roles as $roleModel) {
            $role = new Role($roleModel->tenant_id, $roleModel->name, $roleModel->id);
            foreach ($roleModel->permissions as $permModel) {
                $perm = new Permission($permModel->tenant_id, $permModel->name, $permModel->id);
                $role->grantPermission($perm);
            }
            $user->assignRole($role);
        }
        return $user;
    }
}
