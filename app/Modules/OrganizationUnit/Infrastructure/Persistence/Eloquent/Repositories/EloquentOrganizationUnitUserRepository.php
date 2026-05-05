<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitUser;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitUserRepositoryInterface;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitUserModel;

class EloquentOrganizationUnitUserRepository extends EloquentRepository implements OrganizationUnitUserRepositoryInterface
{
    public function __construct(OrganizationUnitUserModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (OrganizationUnitUserModel $model): OrganizationUnitUser => $this->mapModelToDomainEntity($model));
    }

    public function save(OrganizationUnitUser $organizationUnitUser): OrganizationUnitUser
    {
        $data = [
            'tenant_id' => $organizationUnitUser->getTenantId(),
            'org_unit_id' => $organizationUnitUser->getOrganizationUnitId(),
            'user_id' => $organizationUnitUser->getUserId(),
            'role_id' => $organizationUnitUser->getRole(),
            'is_primary' => $organizationUnitUser->isPrimary(),
        ];

        if ($organizationUnitUser->getId()) {
            $model = $this->update($organizationUnitUser->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var OrganizationUnitUserModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByTenantOrgUnitAndUser(int $tenantId, int $organizationUnitId, int $userId): ?OrganizationUnitUser
    {
        /** @var OrganizationUnitUserModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('org_unit_id', $organizationUnitId)
            ->where('user_id', $userId)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function find(int|string $id, array $columns = ['*']): ?OrganizationUnitUser
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(OrganizationUnitUserModel $model): OrganizationUnitUser
    {
        return new OrganizationUnitUser(
            tenantId: (int) $model->tenant_id,
            organizationUnitId: (int) $model->org_unit_id,
            userId: (int) $model->user_id,
            roleId: $model->role_id !== null ? (int) $model->role_id : null,
            isPrimary: (bool) $model->is_primary,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
