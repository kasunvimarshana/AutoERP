<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Domain\ValueObjects\Address;
use Modules\Core\Domain\ValueObjects\Email;
use Modules\Core\Domain\ValueObjects\PhoneNumber;
use Modules\Core\Domain\ValueObjects\UserPreferences;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\User\Domain\Entities\Permission;
use Modules\User\Domain\Entities\Role;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class EloquentUserRepository extends EloquentRepository implements UserRepositoryInterface
{
    public function __construct(UserModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (UserModel $model): User => $this->mapModelToDomainEntity($model));
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
            'tenant_id' => $user->getTenantId(),
            'email' => $user->getEmail()->value(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'phone' => $user->getPhone()?->value(),
            'address' => $user->getAddress()?->toArray(),
            'preferences' => $user->getPreferences()->toArray(),
            'active' => $user->isActive(),
        ];

        if ($user->getId()) {
            $model = $this->update($user->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var UserModel $model */

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
        /** @var UserModel|null $model */
        $model = $this->model->find($user->getId());
        if ($model) {
            $model->roles()->sync($roleIds);
        }
    }

    /**
     * Find a user by ID and convert to domain entity.
     *
     * {@inheritdoc}
     */
    public function find($id, array $columns = ['*']): ?User
    {
        $this->with(['roles.permissions']);

        return parent::find($id, $columns);
    }

    /**
     * Paginate users and convert each row to a domain entity.
     *
     * {@inheritdoc}
     */
    public function paginate(?int $perPage = null, array $columns = ['*'], ?string $pageName = null, ?int $page = null): LengthAwarePaginator
    {
        $this->with(['roles.permissions']);

        return parent::paginate($perPage, $columns, $pageName, $page);
    }

    private function mapModelToDomainEntity(UserModel $model): User
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
