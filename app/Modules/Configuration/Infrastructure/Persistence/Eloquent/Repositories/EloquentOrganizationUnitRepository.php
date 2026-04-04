<?php

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Configuration\Domain\Entities\OrganizationUnit;
use Modules\Configuration\Domain\RepositoryInterfaces\OrganizationUnitRepositoryInterface;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

class EloquentOrganizationUnitRepository extends EloquentRepository implements OrganizationUnitRepositoryInterface
{
    public function __construct(OrganizationUnitModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?OrganizationUnit
    {
        $m = parent::findById($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByCode(int $tenantId, string $code): ?OrganizationUnit
    {
        $m = $this->model->where('tenant_id', $tenantId)->where('code', $code)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('tenant_id', $tenantId)->paginate($perPage);
    }

    public function create(array $data): OrganizationUnit
    {
        return $this->toEntity(parent::create($data));
    }

    public function update(OrganizationUnit $unit, array $data): OrganizationUnit
    {
        $m = $this->model->findOrFail($unit->id);
        return $this->toEntity(parent::update($m, $data));
    }

    public function delete(OrganizationUnit $unit): bool
    {
        return parent::delete($this->model->findOrFail($unit->id));
    }

    private function toEntity(object $m): OrganizationUnit
    {
        return new OrganizationUnit(
            id: $m->id,
            tenantId: $m->tenant_id,
            name: $m->name,
            code: $m->code,
            type: $m->type,
            parentId: $m->parent_id ?? null,
            address: $m->address ?? null,
            isActive: (bool) $m->is_active,
        );
    }
}
