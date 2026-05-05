<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;

class EloquentOrganizationUnitRepository extends EloquentRepository implements OrganizationUnitRepositoryInterface
{
    public function __construct(OrganizationUnitModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (OrganizationUnitModel $model): OrganizationUnit => $this->mapModelToDomainEntity($model));
    }

    public function save(OrganizationUnit $organizationUnit): OrganizationUnit
    {
        $data = [
            'tenant_id' => $organizationUnit->getTenantId(),
            'type_id' => $organizationUnit->getTypeId(),
            'parent_id' => $organizationUnit->getParentId(),
            'manager_user_id' => $organizationUnit->getManagerUserId(),
            'name' => $organizationUnit->getName(),
            'code' => $organizationUnit->getCode(),
            'image_path' => $organizationUnit->getImagePath(),
            'path' => $organizationUnit->getPath(),
            'depth' => $organizationUnit->getDepth(),
            'metadata' => $organizationUnit->getMetadata(),
            'is_active' => $organizationUnit->isActive(),
            'description' => $organizationUnit->getDescription(),
            'default_revenue_account_id' => $organizationUnit->getDefaultRevenueAccountId(),
            'default_expense_account_id' => $organizationUnit->getDefaultExpenseAccountId(),
            'default_asset_account_id' => $organizationUnit->getDefaultAssetAccountId(),
            'default_liability_account_id' => $organizationUnit->getDefaultLiabilityAccountId(),
            'warehouse_id' => $organizationUnit->getWarehouseId(),
            '_lft' => $organizationUnit->getLeft(),
            '_rgt' => $organizationUnit->getRight(),
            'row_version' => $organizationUnit->getRowVersion(),
        ];

        if ($organizationUnit->getId()) {
            $model = $this->update($organizationUnit->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var OrganizationUnitModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByCode(int $tenantId, string $code): ?OrganizationUnit
    {
        /** @var OrganizationUnitModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function getChildren(int $organizationUnitId): Collection
    {
        /** @var OrganizationUnitModel|null $parent */
        $parent = $this->model->newQuery()->find($organizationUnitId);
        if (! $parent) {
            return new Collection;
        }

        $models = $this->model->newQuery()
            ->where('parent_id', $organizationUnitId)
            ->orderBy('name')
            ->get();

        return $this->toDomainCollection($models);
    }

    public function getDescendants(int $organizationUnitId): Collection
    {
        /** @var OrganizationUnitModel|null $parent */
        $parent = $this->model->newQuery()->find($organizationUnitId);
        if (! $parent) {
            return new Collection;
        }

        // Use nested set model for efficient tree queries
        $models = $this->model->newQuery()
            ->where('_lft', '>', $parent->_lft)
            ->where('_rgt', '<', $parent->_rgt)
            ->orderBy('_lft')
            ->get();

        return $this->toDomainCollection($models);
    }

    public function getAncestors(int $organizationUnitId): Collection
    {
        /** @var OrganizationUnitModel|null $node */
        $node = $this->model->newQuery()->find($organizationUnitId);
        if (! $node) {
            return new Collection;
        }

        // Use nested set model to find ancestors
        $models = $this->model->newQuery()
            ->where('_lft', '<', $node->_lft)
            ->where('_rgt', '>', $node->_rgt)
            ->where('tenant_id', $node->tenant_id)
            ->orderBy('_lft')
            ->get();

        return $this->toDomainCollection($models);
    }

    public function getSiblings(int $organizationUnitId): Collection
    {
        /** @var OrganizationUnitModel|null $node */
        $node = $this->model->newQuery()->find($organizationUnitId);
        if (! $node) {
            return new Collection;
        }

        $models = $this->model->newQuery()
            ->where('parent_id', $node->parent_id)
            ->where('id', '!=', $organizationUnitId)
            ->orderBy('name')
            ->get();

        return $this->toDomainCollection($models);
    }

    public function getByTypeAndLevel(int $tenantId, int $typeId, int $level): Collection
    {
        $models = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('type_id', $typeId)
            ->where('depth', $level)
            ->orderBy('path')
            ->get();

        return $this->toDomainCollection($models);
    }

    public function getRoots(int $tenantId): Collection
    {
        $models = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return $this->toDomainCollection($models);
    }

    public function getHierarchy(int $tenantId): Collection
    {
        $models = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderBy('_lft')
            ->get();

        return $this->toDomainCollection($models);
    }

    private function mapModelToDomainEntity(OrganizationUnitModel $model): OrganizationUnit
    {
        return new OrganizationUnit(
            tenantId: (int) $model->tenant_id,
            typeId: $model->type_id !== null ? (int) $model->type_id : null,
            parentId: $model->parent_id !== null ? (int) $model->parent_id : null,
            managerUserId: $model->manager_user_id !== null ? (int) $model->manager_user_id : null,
            name: (string) $model->name,
            code: $model->code,
            path: $model->path,
            depth: (int) $model->depth,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            isActive: (bool) $model->is_active,
            description: $model->description,

            imagePath: $model->image_path,
            defaultRevenueAccountId: $model->default_revenue_account_id,
            defaultExpenseAccountId: $model->default_expense_account_id,
            defaultAssetAccountId: $model->default_asset_account_id,
            defaultLiabilityAccountId: $model->default_liability_account_id,
            warehouseId: $model->warehouse_id,
            left: (int) $model->_lft,
            right: (int) $model->_rgt,
            rowVersion: (int) ($model->row_version ?? 1),
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
