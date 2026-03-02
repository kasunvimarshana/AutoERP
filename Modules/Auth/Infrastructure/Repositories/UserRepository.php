<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Repositories;

use Modules\Auth\Domain\Contracts\UserRepositoryInterface;
use Modules\Auth\Domain\Entities\User as UserEntity;
use Modules\Auth\Domain\ValueObjects\Email;
use Modules\Auth\Infrastructure\Models\User as UserModel;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?UserEntity
    {
        $model = UserModel::find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByEmail(Email $email, int $tenantId): ?UserEntity
    {
        $model = UserModel::where('email', $email->getValue())
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findActiveByEmail(Email $email): ?UserEntity
    {
        $model = UserModel::where('email', $email->getValue())
            ->where('is_active', true)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function save(UserEntity $user): UserEntity
    {
        $data = [
            'tenant_id'       => $user->getTenantId(),
            'organisation_id' => $user->getOrganisationId(),
            'name'            => $user->getName(),
            'email'           => $user->getEmail()->getValue(),
            'is_active'       => $user->isActive(),
        ];

        if ($user->getPasswordHash() !== null) {
            $data['password'] = $user->getPasswordHash();
        }

        $model = UserModel::updateOrCreate(
            ['id' => $user->getId()],
            $data
        );

        return $this->toDomain($model);
    }

    public function delete(int $id): void
    {
        UserModel::find($id)?->delete();
    }

    public function updateLastLoginAt(int $id): void
    {
        UserModel::where('id', $id)->update(['last_login_at' => now()]);
    }

    private function toDomain(UserModel $model): UserEntity
    {
        return new UserEntity(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            organisationId: (int) $model->organisation_id,
            name: (string) $model->name,
            email: new Email((string) $model->email),
            role: (string) ($model->role_id ?? 'user'),
            isActive: (bool) $model->is_active,
            passwordHash: $model->getRawOriginal('password'),
        );
    }
}
