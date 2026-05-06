<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Tax\Domain\Entities\TaxGroup;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;
use Modules\Tax\Infrastructure\Persistence\Eloquent\Models\TaxGroupModel;

class EloquentTaxGroupRepository extends EloquentRepository implements TaxGroupRepositoryInterface
{
    public function __construct(TaxGroupModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (TaxGroupModel $model): TaxGroup => $this->mapModelToDomainEntity($model));
    }

    public function save(TaxGroup $taxGroup): TaxGroup
    {
        $data = [
            'tenant_id' => $taxGroup->getTenantId(),
            'name' => $taxGroup->getName(),
            'description' => $taxGroup->getDescription(),
        ];

        if ($taxGroup->getId()) {
            $model = $this->update($taxGroup->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var TaxGroupModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByTenantAndName(int $tenantId, string $name): ?TaxGroup
    {
        /** @var TaxGroupModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function find(int|string $id, array $columns = ['*']): ?TaxGroup
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(TaxGroupModel $model): TaxGroup
    {
        return new TaxGroup(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            name: (string) $model->name,
            description: $model->description !== null ? (string) $model->description : null,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
