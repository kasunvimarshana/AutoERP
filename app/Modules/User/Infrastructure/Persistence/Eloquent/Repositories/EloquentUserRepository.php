<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Domain\ValueObjects\Address;
use Modules\Core\Domain\ValueObjects\Email;
use Modules\Core\Domain\ValueObjects\PhoneNumber;
use Modules\Core\Domain\ValueObjects\UserPreferences;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\User\Domain\Entities\Permission;
use Modules\User\Domain\Entities\Role;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Entities\UserAttachment;
use Modules\User\Domain\Entities\UserDevice;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserAttachmentModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserDeviceModel;
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
            'tenant_id'   => $user->getTenantId(),
            'org_unit_id' => $user->getOrgUnitId(),
            'email'       => $user->getEmail()->value(),
            'first_name'  => $user->getFirstName(),
            'last_name'   => $user->getLastName(),
            'phone'       => $user->getPhone()?->value(),
            'address'     => $user->getAddress()?->toArray(),
            'preferences' => $user->getPreferences()->toArray(),
            'status'      => $user->isActive() ? 'active' : 'inactive',
            'avatar'      => $user->getAvatar(),
        ];

        if ($user->getId()) {
            $model = $this->update($user->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var UserModel $model */

        // Sync roles
        if ($user->getRoles()->isNotEmpty()) {
            $roleIds = $user->getRoles()
                ->map(static fn (Role $role): ?int => $role->getId())
                ->filter(static fn (?int $roleId): bool => $roleId !== null)
                ->values()
                ->toArray();
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

    public function changePassword(int $userId, string $hashedPassword): void
    {
        $this->model->where('id', $userId)->update(['password' => $hashedPassword]);
    }

    public function updateAvatar(int $userId, ?string $avatarPath): void
    {
        $this->model->where('id', $userId)->update(['avatar' => $avatarPath]);
    }

    public function verifyPassword(int $userId, string $plainPassword): bool
    {
        /** @var UserModel|null $model */
        $model = $this->model->find($userId);

        return $model && Hash::check($plainPassword, $model->password);
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
            orgUnitId: $model->org_unit_id,
            email: new Email($model->email),
            firstName: $model->first_name,
            lastName: $model->last_name,
            phone: $phone,
            address: $address,
            preferences: $preferences,
            active: $model->status === 'active',
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            avatar: $model->avatar
        );

        foreach ($model->roles as $roleModel) {
            $role = new Role($roleModel->tenant_id, $roleModel->name, $roleModel->id);
            foreach ($roleModel->permissions as $permModel) {
                $perm = new Permission($permModel->tenant_id, $permModel->name, $permModel->id);
                $role->grantPermission($perm);
            }
            $user->assignRole($role);
        }

        if ($model->relationLoaded('attachments')) {
            $user->setAttachments($model->attachments->map(
                fn (UserAttachmentModel $attachment): UserAttachment => $this->mapAttachmentModelToDomainEntity($attachment)
            ));
        }

        if ($model->relationLoaded('devices')) {
            $user->setDevices($model->devices->map(
                fn (UserDeviceModel $device): UserDevice => $this->mapDeviceModelToDomainEntity($device)
            ));
        }

        return $user;
    }

    private function mapAttachmentModelToDomainEntity(UserAttachmentModel $model): UserAttachment
    {
        return new UserAttachment(
            tenantId: (int) $model->tenant_id,
            userId: (int) $model->user_id,
            uuid: (string) $model->uuid,
            name: (string) $model->name,
            filePath: (string) $model->file_path,
            mimeType: (string) $model->mime_type,
            size: (int) $model->size,
            type: $model->type,
            metadata: $model->metadata,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }

    private function mapDeviceModelToDomainEntity(UserDeviceModel $model): UserDevice
    {
        return new UserDevice(
            userId: (int) $model->user_id,
            deviceToken: (string) $model->device_token,
            platform: $model->platform,
            deviceName: $model->device_name,
            lastActiveAt: $model->last_active_at,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
